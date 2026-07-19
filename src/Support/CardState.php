<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Support;

use Kurt\Modules\Loyalty\Models\Card;

final class CardState
{
    /**
     * Build the canonical state array shared by the JSON endpoint and the views.
     *
     * @return array<string, mixed>
     */
    public static function for(Card $card): array
    {
        $program = $card->program;
        $required = (int) $program->stamps_required;
        $count = (int) $card->stamps_count;

        $stamps = [];
        for ($i = 1; $i <= $required; $i++) {
            $stamps[] = [
                'index' => $i,
                'state' => $i <= $count ? 'filled' : 'empty',
            ];
        }

        return [
            'token' => $card->token,
            'code' => strtoupper($card->token),
            'program' => [
                'slug' => $program->slug,
                'name' => $program->name,
                'reward' => $program->reward,
                'stamps_required' => $required,
                'theme' => $program->theme,
                'icon' => $program->icon,
            ],
            'stamps_count' => $count,
            'rewards_earned' => (int) $card->rewards_earned,
            'rewards_redeemed' => (int) $card->rewards_redeemed,
            'rewards_available' => $card->rewardsAvailable(),
            'is_complete' => $card->isComplete(),
            'stamps' => $stamps,
        ];
    }
}
