<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Loyalty\Enums\CardStatus;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\LoyaltyStatsService;
use Kurt\Modules\Loyalty\Services\RedemptionService;
use Kurt\Modules\Loyalty\Services\StampService;

function seedStats(): Program
{
    $program = Program::factory()->create(['stamps_required' => 2, 'cooldown_seconds' => 0]);

    // Card A: 3 stamps -> 1 reward earned, then redeemed.
    $a = Card::factory()->for($program)->create();
    foreach (range(1, 3) as $i) {
        app(StampService::class)->add($a, source: StampSource::Manual);
    }
    app(RedemptionService::class)->redeem($a);

    // Card B: 1 stamp, still active, no reward.
    $b = Card::factory()->for($program)->create();
    app(StampService::class)->add($b, source: StampSource::Manual);

    // Card C: disabled.
    Card::factory()->for($program)->create(['status' => CardStatus::Disabled]);

    return $program;
}

it('aggregates cards, stamps and reward funnels correctly', function () {
    $program = seedStats();

    $stats = app(LoyaltyStatsService::class)->overview();

    expect($stats['totals']['cards_issued'])->toBe(3)
        ->and($stats['totals']['active_cards'])->toBe(2)
        ->and($stats['totals']['stamps_granted'])->toBe(4)
        ->and($stats['totals']['rewards_earned'])->toBe(1)
        ->and($stats['totals']['rewards_redeemed'])->toBe(1)
        ->and($stats['totals']['redemption_rate'])->toBe(1.0);

    expect($stats['programs'])->toHaveCount(1)
        ->and($stats['programs'][0]['program_id'])->toBe((int) $program->getKey())
        ->and($stats['programs'][0]['stamps_granted'])->toBe(4);
});

it('reports a zero redemption rate when nothing has been redeemed', function () {
    $program = Program::factory()->create(['stamps_required' => 5, 'cooldown_seconds' => 0]);
    $card = Card::factory()->for($program)->create();
    app(StampService::class)->add($card, source: StampSource::Manual);

    $stats = app(LoyaltyStatsService::class)->overview();

    expect($stats['totals']['rewards_earned'])->toBe(0)
        ->and($stats['totals']['redemption_rate'])->toBe(0.0);
});

it('serves the stats endpoint as json to staff', function () {
    Gate::define('loyalty:staff', fn ($user = null) => true);
    seedStats();

    // The stats endpoint adopts the shared Core envelope: the aggregate payload
    // is wrapped under `data`.
    $this->getJson(route('loyalty.stats'))
        ->assertOk()
        ->assertJsonPath('data.totals.cards_issued', 3)
        ->assertJsonPath('data.totals.stamps_granted', 4)
        ->assertJsonStructure(['data' => ['range', 'totals', 'programs']]);
});

it('wraps the stats payload in the core envelope', function () {
    Gate::define('loyalty:staff', fn ($user = null) => true);
    seedStats();

    $response = $this->getJson(route('loyalty.stats'))->assertOk();

    // Envelope contract: a single top-level `data` key, no bare `totals`.
    expect(array_keys($response->json()))->toBe(['data']);
});

it('returns the core error envelope for an unknown program filter', function () {
    Gate::define('loyalty:staff', fn ($user = null) => true);

    $this->getJson(route('loyalty.stats', ['program' => 'does-not-exist']))
        ->assertNotFound()
        ->assertJsonPath('message', 'Program not found.')
        ->assertJsonStructure(['message', 'errors']);
});

it('blocks the stats endpoint when the staff gate is undefined', function () {
    $this->getJson(route('loyalty.stats'))->assertForbidden();
});

it('runs the loyalty:stats command', function () {
    seedStats();

    $this->artisan('loyalty:stats')->assertSuccessful();
});
