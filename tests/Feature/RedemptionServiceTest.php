<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Events\RewardRedeemed;
use Kurt\Modules\Loyalty\Exceptions\NoRewardAvailableException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\RedemptionService;

it('redeems a reward and resets stamps when reset_on_reward is true', function () {
    Event::fake();
    $program = Program::factory()->create(['stamps_required' => 3, 'reset_on_reward' => true]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 3, 'rewards_earned' => 1]);

    app(RedemptionService::class)->redeem($card, redeemedBy: 'till-1');

    $card->refresh();
    expect($card->stamps_count)->toBe(0)
        ->and($card->rewards_redeemed)->toBe(1)
        ->and($card->redemptions()->count())->toBe(1);
    Event::assertDispatched(RewardRedeemed::class);
});

it('rolls the surplus over when reset_on_reward is false', function () {
    $program = Program::factory()->create(['stamps_required' => 3, 'reset_on_reward' => false]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 4, 'rewards_earned' => 1]);

    app(RedemptionService::class)->redeem($card);

    expect($card->refresh()->stamps_count)->toBe(1);
});

it('snapshots the reward on the redemption row', function () {
    $program = Program::factory()->create(['stamps_required' => 1]);
    $program->setTranslation('reward', 'en', 'Free latte')->save();
    $card = Card::factory()->for($program)->create(['stamps_count' => 1, 'rewards_earned' => 1]);

    app(RedemptionService::class)->redeem($card);

    expect($card->redemptions()->first()->reward)->toBe(['en' => 'Free latte']);
});

it('refuses to redeem when the card has no earned reward', function () {
    $program = Program::factory()->create(['stamps_required' => 3]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 1, 'rewards_earned' => 0]);

    app(RedemptionService::class)->redeem($card);
})->throws(NoRewardAvailableException::class);
