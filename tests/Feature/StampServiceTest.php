<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\CardCompleted;
use Kurt\Modules\Loyalty\Events\StampAdded;
use Kurt\Modules\Loyalty\Exceptions\DailyStampLimitReachedException;
use Kurt\Modules\Loyalty\Exceptions\StampThrottledException;
use Kurt\Modules\Loyalty\Exceptions\VoucherAlreadyRedeemedException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\StampService;
use Kurt\Modules\Loyalty\Services\VoucherService;

it('adds a stamp, increments the counter and logs immutably', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 3, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);

    $card->refresh();
    expect($card->stamps_count)->toBe(1)
        ->and($card->stamps()->count())->toBe(1)
        ->and($card->last_stamped_at)->not->toBeNull();
    Event::assertDispatched(StampAdded::class);
});

it('rejects a second stamp inside the cooldown window', function () {
    $program = Program::factory()->create(['cooldown_seconds' => 60]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);
})->throws(StampThrottledException::class);

it('enforces the daily stamp limit', function () {
    $program = Program::factory()->create(['cooldown_seconds' => 0, 'max_per_day' => 1]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);
})->throws(DailyStampLimitReachedException::class);

it('fires CardCompleted and credits rewards_earned once when reaching the goal', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 2, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);

    expect($card->refresh()->rewards_earned)->toBe(1);
    Event::assertDispatched(CardCompleted::class, 1);
});

it('credits a second reward for a rollover card that crosses two goals without redeeming', function () {
    $program = Program::factory()->create([
        'stamps_required' => 2,
        'cooldown_seconds' => 0,
        'reset_on_reward' => false,
    ]);
    $card = Card::factory()->for($program)->create();

    foreach (range(1, 4) as $i) {
        app(StampService::class)->add($card, source: StampSource::Manual);
    }

    expect($card->refresh()->rewards_earned)->toBe(2)
        ->and($card->stamps_count)->toBe(4);
});

it('does not re-credit when stamps continue past the goal without redemption', function () {
    $program = Program::factory()->create(['stamps_required' => 2, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();

    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);
    app(StampService::class)->add($card, source: StampSource::Manual);

    expect($card->refresh()->rewards_earned)->toBe(1)
        ->and($card->stamps_count)->toBe(3);
});

it('redeems a voucher exactly once and adds its stamps', function () {
    $program = Program::factory()->create(['cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();
    $voucher = app(VoucherService::class)->issue($program, stamps: 1);

    app(VoucherService::class)->redeem($voucher, $card);

    expect($card->refresh()->stamps_count)->toBe(1)
        ->and($voucher->refresh()->status)->toBe('redeemed');

    expect(fn () => app(VoucherService::class)->redeem($voucher, $card))
        ->toThrow(VoucherAlreadyRedeemedException::class);
});

it('adds every stamp of a multi-stamp voucher despite cooldown', function () {
    $program = Program::factory()->create(['cooldown_seconds' => 60]);
    $card = Card::factory()->for($program)->create();
    $voucher = app(VoucherService::class)->issue($program, stamps: 3);

    app(VoucherService::class)->redeem($voucher, $card);

    expect($card->refresh()->stamps_count)->toBe(3);
});
