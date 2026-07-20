<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

it('registers the api endpoints but not the shipped html pages', function () {
    expect(Route::has('loyalty.card.state'))->toBeTrue()
        ->and(Route::has('loyalty.card.store'))->toBeTrue()
        ->and(Route::has('loyalty.card.redeem-voucher'))->toBeTrue()
        ->and(Route::has('loyalty.card.apple'))->toBeTrue()
        ->and(Route::has('loyalty.terminal.stamp'))->toBeTrue()
        ->and(Route::has('loyalty.card.show'))->toBeFalse()
        ->and(Route::has('loyalty.terminal.index'))->toBeFalse();
});

it('still serves state json in api mode', function () {
    $card = Card::factory()->for(Program::factory()->create())->create(['stamps_count' => 2]);

    $this->getJson(route('loyalty.card.state', $card->token))
        ->assertOk()
        ->assertJsonPath('stamps_count', 2);
});
