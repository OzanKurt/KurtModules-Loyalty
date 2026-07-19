<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Enums;

enum WalletPlatform: string
{
    case Apple = 'apple';
    case Google = 'google';
}
