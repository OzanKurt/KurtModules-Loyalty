<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\WalletPass;
use Kurt\Modules\Loyalty\Models\WalletRegistration;

beforeEach(function () {
    $this->card = Card::factory()->for(Program::factory()->create())->create();
    WalletPass::query()->create([
        'card_id' => $this->card->id,
        'platform' => 'apple',
        'external_id' => $this->card->token,
        'auth_token' => 'secret-token',
    ]);
});

it('registers a device for pass updates with a valid ApplePass token', function () {
    $this->postJson(
        route('loyalty.apple.register', ['device' => 'dev-1', 'passType' => 'pass.com.x', 'serial' => $this->card->token]),
        ['pushToken' => 'push-abc'],
        ['Authorization' => 'ApplePass secret-token'],
    )->assertCreated();

    expect(WalletRegistration::query()
        ->where('device_library_id', 'dev-1')
        ->where('pass_serial', $this->card->token)
        ->where('push_token', 'push-abc')
        ->exists())->toBeTrue();
});

it('rejects device registration with a bad token', function () {
    $this->postJson(
        route('loyalty.apple.register', ['device' => 'dev-1', 'passType' => 'pass.com.x', 'serial' => $this->card->token]),
        ['pushToken' => 'push-abc'],
        ['Authorization' => 'ApplePass wrong'],
    )->assertStatus(401);

    expect(WalletRegistration::query()->count())->toBe(0);
});

it('lists the serials registered to a device', function () {
    WalletRegistration::query()->create([
        'device_library_id' => 'dev-1',
        'pass_serial' => $this->card->token,
        'push_token' => 'p',
    ]);

    $this->getJson(route('loyalty.apple.serials', ['device' => 'dev-1', 'passType' => 'pass.com.x']))
        ->assertOk()
        ->assertJsonPath('serialNumbers.0', $this->card->token);
});

it('unregisters a device', function () {
    WalletRegistration::query()->create([
        'device_library_id' => 'dev-1',
        'pass_serial' => $this->card->token,
        'push_token' => 'p',
    ]);

    $this->deleteJson(
        route('loyalty.apple.unregister', ['device' => 'dev-1', 'passType' => 'pass.com.x', 'serial' => $this->card->token]),
        [],
        ['Authorization' => 'ApplePass secret-token'],
    )->assertNoContent();

    expect(WalletRegistration::query()->count())->toBe(0);
});
