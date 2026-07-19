<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Enums;

enum CardStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Disabled = 'disabled';
}
