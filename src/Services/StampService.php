<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\CardCompleted;
use Kurt\Modules\Loyalty\Events\StampAdded;
use Kurt\Modules\Loyalty\Events\TierReached;
use Kurt\Modules\Loyalty\Exceptions\DailyStampLimitReachedException;
use Kurt\Modules\Loyalty\Exceptions\StampThrottledException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\ProgramTier;
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

            $before = $card->stamps_count;

            $card->stamps()->create([
                'voucher_id' => $voucher?->getKey(),
                'source' => $source->value,
                'granted_by' => $grantedBy,
                'created_at' => now(),
            ]);

            $after = $before + 1;

            $card->forceFill([
                'stamps_count' => $after,
                'last_stamped_at' => now(),
            ])->save();

            event(new StampAdded($card, $source));

            $this->creditRewards($card, $before, $after);

            return $card->refresh();
        });
    }

    /**
     * Credit rewards for the thresholds this stamp just crossed.
     *
     * If the program defines tiers, each tier whose absolute threshold falls in
     * `($before, $after]` is earned once, firing a `TierReached` event. Programs
     * with no tier rows fall back to the original repeating single-threshold
     * behavior (which preserves rollover crediting for `reset_on_reward = false`).
     */
    private function creditRewards(Card $card, int $before, int $after): void
    {
        $tiers = $card->program->tiers;

        if ($tiers->isNotEmpty()) {
            /** @var Collection<int, ProgramTier> $crossed */
            $crossed = $tiers->filter(
                fn (ProgramTier $tier): bool => $tier->threshold > $before && $tier->threshold <= $after
            )->values();

            if ($crossed->isEmpty()) {
                return;
            }

            $card->forceFill(['rewards_earned' => $card->rewards_earned + $crossed->count()])->save();

            foreach ($crossed as $tier) {
                event(new TierReached($card, $tier));
            }

            event(new CardCompleted($card));

            return;
        }

        // Credit every reward threshold crossed by this stamp — not just the
        // first — so rollover cards (reset_on_reward = false) that reach a
        // multiple of stamps_required without an intervening redemption earn
        // each reward. In reset mode this still credits exactly once per goal.
        $required = (int) $card->program->stamps_required;
        $earned = $required > 0 ? intdiv($after, $required) - intdiv($before, $required) : 0;

        if ($earned > 0) {
            $card->forceFill(['rewards_earned' => $card->rewards_earned + $earned])->save();
            event(new CardCompleted($card));
        }
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
