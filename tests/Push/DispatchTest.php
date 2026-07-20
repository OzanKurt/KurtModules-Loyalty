<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Jobs\PushWalletUpdate;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\RedemptionService;
use Kurt\Modules\Loyalty\Services\StampService;

it('dispatches a wallet push job when a stamp is added and push is enabled', function () {
    Queue::fake();
    $card = Card::factory()->for(Program::factory()->create(['cooldown_seconds' => 0]))->create();

    app(StampService::class)->add($card, StampSource::Manual);

    Queue::assertPushed(PushWalletUpdate::class);
});

it('dispatches a wallet push job when a reward is redeemed', function () {
    Queue::fake();
    $program = Program::factory()->create(['stamps_required' => 2]);
    $card = Card::factory()->for($program)->create(['stamps_count' => 2, 'rewards_earned' => 1]);

    app(RedemptionService::class)->redeem($card);

    Queue::assertPushed(PushWalletUpdate::class);
});
