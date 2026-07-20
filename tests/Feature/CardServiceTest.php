<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Loyalty\Enums\IdentityMode;
use Kurt\Modules\Loyalty\Events\CardClaimed;
use Kurt\Modules\Loyalty\Events\CardCreated;
use Kurt\Modules\Loyalty\Exceptions\CardAlreadyClaimedException;
use Kurt\Modules\Loyalty\Exceptions\CardNotClaimableException;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\CardService;

beforeEach(function () {
    $this->program = Program::factory()->create();
    $this->service = app(CardService::class);
});

it('creates an anonymous card with a unique token and fires an event', function () {
    Event::fake();

    $card = $this->service->create($this->program);

    expect($card->token)->not->toBeEmpty()
        ->and($card->user_id)->toBeNull()
        ->and($card->stamps_count)->toBe(0)
        ->and($card->program->is($this->program))->toBeTrue();
    Event::assertDispatched(CardCreated::class);
});

it('generates distinct long tokens and short codes for successive cards', function () {
    $a = $this->service->create($this->program);
    $b = $this->service->create($this->program);

    expect($a->token)->not->toBe($b->token)
        ->and($a->code)->not->toBe($b->code)
        ->and(strlen($a->token))->toBe(32)   // 128-bit hex secret
        ->and(strlen($a->code))->toBe(8);    // short display code
});

it('refuses to re-claim an already claimed card', function () {
    config()->set('loyalty.identity_mode', IdentityMode::AnonymousClaimable->value);
    $card = $this->service->create($this->program);
    $this->service->claim($card, ['email' => 'first@owner.com']);

    $this->service->claim($card->refresh(), ['email' => 'thief@evil.com']);
})->throws(CardAlreadyClaimedException::class);

it('claims an anonymous card by email when the mode allows', function () {
    config()->set('loyalty.identity_mode', IdentityMode::AnonymousClaimable->value);
    Event::fake();

    $card = $this->service->create($this->program);
    $card = $this->service->claim($card, ['email' => 'a@b.com']);

    expect($card->email)->toBe('a@b.com');
    Event::assertDispatched(CardClaimed::class);
});

it('refuses to claim when identity mode is anonymous', function () {
    config()->set('loyalty.identity_mode', IdentityMode::Anonymous->value);
    $card = $this->service->create($this->program);

    $this->service->claim($card, ['email' => 'a@b.com']);
})->throws(CardNotClaimableException::class);
