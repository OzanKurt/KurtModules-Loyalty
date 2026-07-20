<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Events;

use Kurt\Modules\Loyalty\Models\Card;

final class RewardExpired
{
    public function __construct(
        public readonly Card $card,
        public readonly int $expiredRewards,
    ) {}
}
