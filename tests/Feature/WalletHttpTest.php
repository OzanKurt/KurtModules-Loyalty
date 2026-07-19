<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Models\Card;

it('returns 404 for the apple pass when apple wallet is disabled', function () {
    $card = Card::factory()->create();

    $this->get(route('loyalty.card.apple', $card->token))->assertNotFound();
});

it('returns 404 for the google pass when google wallet is disabled', function () {
    $card = Card::factory()->create();

    $this->get(route('loyalty.card.google', $card->token))->assertNotFound();
});

it('hides wallet buttons on the card page when no provider is configured', function () {
    $card = Card::factory()->create();

    $this->get(route('loyalty.card.show', $card->token))
        ->assertOk()
        ->assertDontSee('Add to Apple Wallet')
        ->assertDontSee('Add to Google Wallet');
});
