<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Enums\IdentityMode;

return [
    /*
    |--------------------------------------------------------------------------
    | Identity mode
    |--------------------------------------------------------------------------
    | How a card is owned:
    |   IdentityMode::Anonymous          - token URL only, never claimable
    |   IdentityMode::User               - always bound to an authenticated user
    |   IdentityMode::AnonymousClaimable - starts anonymous, claimable later
    */
    'identity_mode' => IdentityMode::AnonymousClaimable->value,

    /*
    |--------------------------------------------------------------------------
    | Anti-fraud guards (defaults; per-program columns win)
    |--------------------------------------------------------------------------
    */
    'throttle' => [
        'cooldown_seconds' => 30,   // min seconds between stamps on one card
        'max_per_day' => null,      // null = unlimited stamps/day per card
    ],

    /*
    |--------------------------------------------------------------------------
    | Reward behaviour when a card completes
    |--------------------------------------------------------------------------
    | true  = reset stamp count to 0 on redemption
    | false = roll the surplus over (count - stamps_required)
    */
    'reset_on_reward' => true,

    /*
    |--------------------------------------------------------------------------
    | HTTP routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'loyalty',
        'domain' => null,
        'middleware' => ['web'],
        // Applied to public write endpoints (create / claim / voucher redeem).
        'rate_limit' => '30,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff terminal
    |--------------------------------------------------------------------------
    | Middleware guarding the staff terminal. Defaults to the `loyalty:staff`
    | gate, which is undefined (deny-all) until the consuming app defines it.
    */
    'staff' => [
        'middleware' => ['can:loyalty:staff'],
    ],
];
