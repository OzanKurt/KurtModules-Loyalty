<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\CardCompleted;
use Kurt\Modules\Loyalty\Events\StampAdded;
use Kurt\Modules\Loyalty\Exceptions\DailyStampLimitReachedException;
use Kurt\Modules\Loyalty\Exceptions\StampThrottledException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Voucher;

final class StampService
{
    public function add(
        Card $card,
        StampSource $source,
        ?Voucher $voucher = null,
        ?string $grantedBy = null,
        bool $bypassThrottle = false,
    ): Card {
        return DB::transaction(function () use ($card, $source, $voucher, $grantedBy, $bypassThrottle) {
            /** @var Card $card */
            $card = Card::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();

            if (! $bypassThrottle) {
                $this->guardThrottle($card);
            }
            $this->guardDailyLimit($card);

            $required = (int) $card->program->stamps_required;
            $before = $card->stamps_count;

            $card->stamps()->create([
                'voucher_id' => $voucher?->getKey(),
                'source' => $source->value,
                'granted_by' => $grantedBy,
                'created_at' => now(),
            ]);

            $card->forceFill([
                'stamps_count' => $before + 1,
                'last_stamped_at' => now(),
            ])->save();

            event(new StampAdded($card, $source));

            if ($before < $required && ($before + 1) >= $required) {
                $card->forceFill(['rewards_earned' => $card->rewards_earned + 1])->save();
                event(new CardCompleted($card));
            }

            return $card->refresh();
        });
    }

    private function guardThrottle(Card $card): void
    {
        $cooldown = (int) $card->program->cooldown_seconds;

        if ($cooldown > 0
            && $card->last_stamped_at !== null
            && abs($card->last_stamped_at->diffInSeconds(now())) < $cooldown) {
            throw new StampThrottledException("Stamp rejected: {$cooldown}s cooldown not elapsed.");
        }
    }

    private function guardDailyLimit(Card $card): void
    {
        $max = $card->program->max_per_day;

        if ($max === null) {
            return;
        }

        $today = $card->stamps()->whereDate('created_at', now()->toDateString())->count();

        if ($today >= $max) {
            throw new DailyStampLimitReachedException("Daily stamp limit of {$max} reached.");
        }
    }
}
