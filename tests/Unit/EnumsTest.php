<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Enums\CardStatus;
use Kurt\Modules\Loyalty\Enums\IdentityMode;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Enums\WalletPlatform;

it('exposes identity modes', function () {
    expect(IdentityMode::from('anonymous_claimable'))->toBe(IdentityMode::AnonymousClaimable)
        ->and(IdentityMode::cases())->toHaveCount(3);
});

it('exposes stamp sources, card statuses and wallet platforms', function () {
    expect(StampSource::from('staff_terminal'))->toBe(StampSource::StaffTerminal)
        ->and(CardStatus::from('active'))->toBe(CardStatus::Active)
        ->and(WalletPlatform::cases())->toHaveCount(2);
});
