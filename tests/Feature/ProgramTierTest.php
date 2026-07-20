<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\CardCompleted;
use Kurt\Modules\Loyalty\Events\TierReached;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\ProgramTier;
use Kurt\Modules\Loyalty\Services\StampService;
use Kurt\Modules\Loyalty\Services\VoucherService;

function tieredProgram(): Program
{
    $program = Program::factory()->create(['stamps_required' => 10, 'cooldown_seconds' => 0]);

    ProgramTier::factory()->for($program)->create(['threshold' => 3, 'reward' => ['en' => 'Cookie'], 'position' => 1]);
    ProgramTier::factory()->for($program)->create(['threshold' => 6, 'reward' => ['en' => 'Coffee'], 'position' => 2]);
    ProgramTier::factory()->for($program)->create(['threshold' => 10, 'reward' => ['en' => 'Mug'], 'position' => 3]);

    return $program;
}

it('relates a program to its tiers ordered by threshold', function () {
    $program = Program::factory()->create();
    ProgramTier::factory()->for($program)->create(['threshold' => 9]);
    ProgramTier::factory()->for($program)->create(['threshold' => 3]);

    expect($program->tiers()->pluck('threshold')->all())->toBe([3, 9]);
});

it('earns a tier reward exactly when each threshold is crossed', function () {
    Event::fake();
    $program = tieredProgram();
    $card = Card::factory()->for($program)->create();

    foreach (range(1, 3) as $i) {
        app(StampService::class)->add($card, source: StampSource::Manual);
    }
    expect($card->refresh()->rewards_earned)->toBe(1);

    // Stamps 4 and 5 cross no tier.
    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);
    expect($card->refresh()->rewards_earned)->toBe(1);

    app(StampService::class)->add($card, source: StampSource::Manual); // 6th -> tier 2
    expect($card->refresh()->rewards_earned)->toBe(2);

    Event::assertDispatched(TierReached::class, 2);
    Event::assertDispatched(fn (TierReached $e) => $e->tier->threshold === 3);
    Event::assertDispatched(fn (TierReached $e) => $e->tier->threshold === 6);
});

it('credits multiple tiers crossed at once by a multi-stamp voucher', function () {
    $program = tieredProgram();
    $card = Card::factory()->for($program)->create();

    // A single 6-stamp voucher crosses the 3 and 6 thresholds.
    $voucher = app(VoucherService::class)->issue($program, stamps: 6);
    app(VoucherService::class)->redeem($voucher, $card);

    expect($card->refresh()->stamps_count)->toBe(6)
        ->and($card->rewards_earned)->toBe(2);
});

it('does not earn extra rewards past the top tier', function () {
    $program = tieredProgram();
    $card = Card::factory()->for($program)->create();

    foreach (range(1, 12) as $i) {
        app(StampService::class)->add($card, source: StampSource::Manual);
    }

    // Tiers at 3, 6, 10 -> exactly three rewards, nothing beyond the ladder.
    expect($card->refresh()->rewards_earned)->toBe(3);
});

it('leaves single-threshold programs (no tiers) behaving exactly as before', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 2, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);

    expect($card->refresh()->rewards_earned)->toBe(1);
    Event::assertDispatched(CardCompleted::class, 1);
    Event::assertNotDispatched(TierReached::class);
});
