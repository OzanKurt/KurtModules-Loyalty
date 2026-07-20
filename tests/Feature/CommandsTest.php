<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Console\Commands\DemoCommand;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\VoucherService;

it('prunes expired pending vouchers', function () {
    $program = Program::factory()->create();
    $expired = app(VoucherService::class)->issue($program, expiresInSeconds: -60);
    $active = app(VoucherService::class)->issue($program, expiresInSeconds: 3600);

    $this->artisan('loyalty:prune-vouchers')->assertSuccessful();

    expect($expired->refresh()->status)->toBe('expired')
        ->and($active->refresh()->status)->toBe('pending');
});

it('creates a demo program and card', function () {
    $this->artisan('loyalty:demo', ['--stamps' => 5])->assertSuccessful();

    expect(Program::query()->count())->toBe(1)
        ->and(Card::query()->count())->toBe(1)
        ->and(Program::query()->first()->stamps_required)->toBe(5);
});

it('exposes a --force flag to override the production guard', function () {
    $definition = (new DemoCommand)->getDefinition();

    expect($definition->hasOption('force'))->toBeTrue();
});

it('reports wallet configuration', function () {
    $this->artisan('loyalty:wallet-check')->assertSuccessful();
});
