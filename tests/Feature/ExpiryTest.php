<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Events\RewardExpired;
use Kurt\Modules\Loyalty\Events\StampsExpired;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

it('expires stale stamps and fires StampsExpired', function () {
    Event::fake();
    $program = Program::factory()->create(['stamp_expiry_days' => 30]);
    $card = Card::factory()->for($program)->create();
    $card->forceFill(['stamps_count' => 3, 'last_stamped_at' => now()->subDays(40)])->save();

    $this->artisan('loyalty:expire')->assertSuccessful();

    expect($card->refresh()->stamps_count)->toBe(0);
    Event::assertDispatched(fn (StampsExpired $e) => $e->expiredStamps === 3 && $e->card->is($card));
});

it('expires unredeemed earned rewards and fires RewardExpired', function () {
    Event::fake();
    $program = Program::factory()->create(['reward_expiry_days' => 30]);
    $card = Card::factory()->for($program)->create();
    $card->forceFill([
        'rewards_earned' => 2,
        'rewards_redeemed' => 1,
        'last_stamped_at' => now()->subDays(40),
    ])->save();

    $this->artisan('loyalty:expire')->assertSuccessful();

    $card->refresh();
    expect($card->rewards_expired)->toBe(1)
        ->and($card->rewardsAvailable())->toBe(0);
    Event::assertDispatched(fn (RewardExpired $e) => $e->expiredRewards === 1);
});

it('leaves recent stamps and rewards untouched', function () {
    $program = Program::factory()->create(['stamp_expiry_days' => 30, 'reward_expiry_days' => 30]);
    $card = Card::factory()->for($program)->create();
    $card->forceFill([
        'stamps_count' => 3,
        'rewards_earned' => 1,
        'last_stamped_at' => now()->subDays(5),
    ])->save();

    $this->artisan('loyalty:expire')->assertSuccessful();

    $card->refresh();
    expect($card->stamps_count)->toBe(3)
        ->and($card->rewards_expired)->toBe(0);
});

it('expires nothing when no expiry window is configured', function () {
    Event::fake();
    config()->set('loyalty.expiry.stamp_days', null);
    config()->set('loyalty.expiry.reward_days', null);

    $program = Program::factory()->create(['stamp_expiry_days' => null, 'reward_expiry_days' => null]);
    $card = Card::factory()->for($program)->create();
    $card->forceFill([
        'stamps_count' => 3,
        'rewards_earned' => 1,
        'last_stamped_at' => now()->subDays(400),
    ])->save();

    $this->artisan('loyalty:expire')->assertSuccessful();

    $card->refresh();
    expect($card->stamps_count)->toBe(3)
        ->and($card->rewards_expired)->toBe(0);
    Event::assertNotDispatched(StampsExpired::class);
    Event::assertNotDispatched(RewardExpired::class);
});

it('falls back to the config default when the program column is null', function () {
    config()->set('loyalty.expiry.stamp_days', 10);
    $program = Program::factory()->create(['stamp_expiry_days' => null]);
    $card = Card::factory()->for($program)->create();
    $card->forceFill(['stamps_count' => 2, 'last_stamped_at' => now()->subDays(20)])->save();

    $this->artisan('loyalty:expire')->assertSuccessful();

    expect($card->refresh()->stamps_count)->toBe(0);
});

it('is idempotent — a second run expires nothing more', function () {
    Event::fake();
    $program = Program::factory()->create(['stamp_expiry_days' => 30, 'reward_expiry_days' => 30]);
    $card = Card::factory()->for($program)->create();
    $card->forceFill([
        'stamps_count' => 3,
        'rewards_earned' => 1,
        'last_stamped_at' => now()->subDays(40),
    ])->save();

    $this->artisan('loyalty:expire')->assertSuccessful();
    Event::assertDispatched(StampsExpired::class, 1);
    Event::assertDispatched(RewardExpired::class, 1);

    // Second run: nothing left to expire.
    $this->artisan('loyalty:expire')->assertSuccessful();
    Event::assertDispatched(StampsExpired::class, 1);
    Event::assertDispatched(RewardExpired::class, 1);
});
