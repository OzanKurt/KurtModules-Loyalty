<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\VoucherService;

beforeEach(function () {
    $this->program = Program::factory()->create(['stamps_required' => 3, 'cooldown_seconds' => 0]);
});

it('renders the card page with the data-attribute contract', function () {
    $card = Card::factory()->for($this->program)->create();

    $this->get(route('loyalty.card.show', $card->token))
        ->assertOk()
        ->assertSee('data-loyalty-card', false)
        ->assertSee('data-loyalty-stamp', false)
        ->assertSee('<svg', false)
        ->assertSee($card->token, false);
});

it('returns card state as json', function () {
    $card = Card::factory()->for($this->program)->create(['stamps_count' => 1]);

    $this->getJson(route('loyalty.card.state', $card->token))
        ->assertOk()
        ->assertJsonPath('stamps_count', 1)
        ->assertJsonPath('program.stamps_required', 3)
        ->assertJsonCount(3, 'stamps');
});

it('creates a card via POST', function () {
    $this->postJson(route('loyalty.card.store', $this->program->slug), ['email' => 'a@b.com'])
        ->assertCreated()
        ->assertJsonPath('program.slug', $this->program->slug)
        ->assertJsonPath('stamps_count', 0);

    expect(Card::query()->count())->toBe(1);
});

it('claims a card via POST', function () {
    config()->set('loyalty.identity_mode', 'anonymous_claimable');
    $card = Card::factory()->for($this->program)->create();

    $this->postJson(route('loyalty.card.claim', $card->token), ['email' => 'x@y.com'])
        ->assertOk()
        ->assertJsonPath('token', $card->token);

    expect($card->refresh()->email)->toBe('x@y.com');
});

it('rejects re-claiming an already claimed card with 409', function () {
    config()->set('loyalty.identity_mode', 'anonymous_claimable');
    $card = Card::factory()->for($this->program)->create(['email' => 'owner@x.com']);

    $this->postJson(route('loyalty.card.claim', $card->token), ['email' => 'thief@x.com'])
        ->assertStatus(409);

    expect($card->refresh()->email)->toBe('owner@x.com');
});

it('lets the staff terminal resolve a card by its short code', function () {
    Gate::define('loyalty:staff', fn ($user = null) => true);
    $card = Card::factory()->for($this->program)->create();

    $this->postJson(route('loyalty.terminal.stamp'), ['card_token' => $card->code])
        ->assertOk()
        ->assertJsonPath('stamps_count', 1);
});

it('redeems a voucher onto a card via POST', function () {
    $card = Card::factory()->for($this->program)->create();
    $voucher = app(VoucherService::class)->issue($this->program, stamps: 1);

    $this->postJson(route('loyalty.card.redeem-voucher', ['token' => $card->token, 'voucher' => $voucher->token]))
        ->assertOk()
        ->assertJsonPath('stamps_count', 1);
});

it('blocks the staff terminal when the gate is undefined', function () {
    $card = Card::factory()->for($this->program)->create();

    $this->postJson(route('loyalty.terminal.stamp'), ['card_token' => $card->token])
        ->assertForbidden();
});

it('adds a stamp from the staff terminal when authorized', function () {
    Gate::define('loyalty:staff', fn ($user = null) => true);
    $card = Card::factory()->for($this->program)->create();

    $this->postJson(route('loyalty.terminal.stamp'), ['card_token' => $card->token])
        ->assertOk()
        ->assertJsonPath('stamps_count', 1);
});

it('redeems a reward from the staff terminal when authorized', function () {
    Gate::define('loyalty:staff', fn ($user = null) => true);
    $card = Card::factory()->for($this->program)->create(['stamps_count' => 3, 'rewards_earned' => 1]);

    $this->postJson(route('loyalty.terminal.redeem'), ['card_token' => $card->token])
        ->assertOk()
        ->assertJsonPath('rewards_redeemed', 1)
        ->assertJsonPath('stamps_count', 0);
});
