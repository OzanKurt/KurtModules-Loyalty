<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Events;

use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\ProgramTier;

final class TierReached
{
    public function __construct(
        public readonly Card $card,
        public readonly ProgramTier $tier,
    ) {}
}
