<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\VoucherService;

it('issues a pending single-use voucher', function () {
    $program = Program::factory()->create();

    $voucher = app(VoucherService::class)->issue($program, stamps: 1, source: StampSource::StaffTerminal);

    expect($voucher->status)->toBe('pending')
        ->and($voucher->stamps)->toBe(1)
        ->and($voucher->token)->not->toBeEmpty()
        ->and($voucher->isRedeemable())->toBeTrue();
});

it('marks an expired voucher not redeemable', function () {
    $program = Program::factory()->create();

    $voucher = app(VoucherService::class)->issue($program, expiresInSeconds: -10);

    expect($voucher->isRedeemable())->toBeFalse();
});
