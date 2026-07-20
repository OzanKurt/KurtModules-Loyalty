<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\CardService;
use Kurt\Modules\Loyalty\Services\StampService;

it('registers no loyalty http routes in headless mode', function () {
    expect(Route::has('loyalty.card.state'))->toBeFalse()
        ->and(Route::has('loyalty.card.show'))->toBeFalse()
        ->and(Route::has('loyalty.terminal.stamp'))->toBeFalse()
        ->and(Route::has('loyalty.stats'))->toBeFalse();
});

it('still exposes the domain services in headless mode', function () {
    $program = Program::factory()->create();
    $card = app(CardService::class)->create($program);
    app(StampService::class)->add($card, StampSource::Manual);

    expect($card->refresh()->stamps_count)->toBe(1);
});
