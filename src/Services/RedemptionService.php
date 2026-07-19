<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Events\RewardRedeemed;
use Kurt\Modules\Loyalty\Exceptions\NoRewardAvailableException;
use Kurt\Modules\Loyalty\Models\Card;

final class RedemptionService
{
    public function redeem(Card $card, ?string $redeemedBy = null): Card
    {
        return DB::transaction(function () use ($card, $redeemedBy) {
            /** @var Card $card */
            $card = Card::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();

            if ($card->rewardsAvailable() < 1) {
                throw new NoRewardAvailableException('No reward available to redeem.');
            }

            $card->redemptions()->create([
                'reward' => $card->program->getTranslations('reward'),
                'redeemed_by' => $redeemedBy,
                'created_at' => now(),
            ]);

            $required = (int) $card->program->stamps_required;
            $newCount = $card->program->reset_on_reward
                ? 0
                : max(0, $card->stamps_count - $required);

            $card->forceFill([
                'rewards_redeemed' => $card->rewards_redeemed + 1,
                'stamps_count' => $newCount,
            ])->save();

            event(new RewardRedeemed($card));

            return $card->refresh();
        });
    }
}
