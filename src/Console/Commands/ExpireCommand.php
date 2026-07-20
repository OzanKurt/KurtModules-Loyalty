<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Events\RewardExpired;
use Kurt\Modules\Loyalty\Events\StampsExpired;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

/**
 * Prune stale stamps and unredeemed earned rewards according to each program's
 * expiry window. Idempotent: once a card's stamps are zeroed / rewards voided it
 * no longer matches, so repeat runs are safe no-ops.
 */
final class ExpireCommand extends Command
{
    protected $signature = 'loyalty:expire {--program= : Limit to one program (id or slug)}';

    protected $description = 'Void stamps and earned rewards that have passed their program expiry window.';

    public function handle(): int
    {
        $programOption = $this->option('program');
        $programFilter = is_string($programOption) && $programOption !== '' ? $programOption : null;

        $programs = Program::query()
            ->when($programFilter !== null, function ($q) use ($programFilter) {
                $q->where('slug', $programFilter)
                    ->when(ctype_digit((string) $programFilter), fn ($qq) => $qq->orWhere('id', (int) $programFilter));
            })
            ->get();

        $stampsVoided = 0;
        $rewardsVoided = 0;

        foreach ($programs as $program) {
            $stampsVoided += $this->expireStamps($program);
            $rewardsVoided += $this->expireRewards($program);
        }

        $this->info("Expired stamps on {$stampsVoided} card(s) and voided rewards on {$rewardsVoided} card(s).");

        return self::SUCCESS;
    }

    private function expireStamps(Program $program): int
    {
        $days = $program->stampExpiryDays();

        if ($days === null) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $affected = 0;

        Card::query()
            ->where('program_id', $program->getKey())
            ->where('stamps_count', '>', 0)
            ->whereNotNull('last_stamped_at')
            ->where('last_stamped_at', '<=', $cutoff)
            ->select('id')
            ->chunkById(200, function ($cards) use ($cutoff, &$affected) {
                foreach ($cards as $card) {
                    $affected += $this->voidStamps((int) $card->getKey(), $cutoff);
                }
            });

        return $affected;
    }

    private function expireRewards(Program $program): int
    {
        $days = $program->rewardExpiryDays();

        if ($days === null) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $affected = 0;

        Card::query()
            ->where('program_id', $program->getKey())
            ->whereRaw('rewards_earned - rewards_redeemed - rewards_expired > 0')
            ->whereNotNull('last_stamped_at')
            ->where('last_stamped_at', '<=', $cutoff)
            ->select('id')
            ->chunkById(200, function ($cards) use ($cutoff, &$affected) {
                foreach ($cards as $card) {
                    $affected += $this->voidRewards((int) $card->getKey(), $cutoff);
                }
            });

        return $affected;
    }

    private function voidStamps(int $cardId, Carbon $cutoff): int
    {
        return DB::transaction(function () use ($cardId, $cutoff): int {
            /** @var Card $card */
            $card = Card::query()->whereKey($cardId)->lockForUpdate()->firstOrFail();

            // Re-check under the lock so a stamp added since the scan (or a prior
            // run in the same batch) can't be wrongly voided.
            if ($card->stamps_count <= 0
                || $card->last_stamped_at === null
                || $card->last_stamped_at->gt($cutoff)) {
                return 0;
            }

            $expired = $card->stamps_count;
            $card->forceFill(['stamps_count' => 0])->save();

            event(new StampsExpired($card, $expired));

            return 1;
        });
    }

    private function voidRewards(int $cardId, Carbon $cutoff): int
    {
        return DB::transaction(function () use ($cardId, $cutoff): int {
            /** @var Card $card */
            $card = Card::query()->whereKey($cardId)->lockForUpdate()->firstOrFail();

            $available = $card->rewardsAvailable();

            if ($available <= 0
                || $card->last_stamped_at === null
                || $card->last_stamped_at->gt($cutoff)) {
                return 0;
            }

            $card->forceFill(['rewards_expired' => $card->rewards_expired + $available])->save();

            event(new RewardExpired($card, $available));

            return 1;
        });
    }
}
