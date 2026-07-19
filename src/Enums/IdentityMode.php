<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Enums;

enum IdentityMode: string
{
    case Anonymous = 'anonymous';
    case User = 'user';
    case AnonymousClaimable = 'anonymous_claimable';
}
