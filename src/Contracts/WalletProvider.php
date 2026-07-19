<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Contracts;

use Kurt\Modules\Loyalty\Models\Card;

interface WalletProvider
{
    /**
     * Whether this provider has the credentials it needs to operate.
     */
    public function isConfigured(): bool;

    /**
     * Build the provider-specific pass representation for a card.
     *
     * @return array<string, mixed>
     */
    public function buildPass(Card $card): array;

    /**
     * The stable pass identifier / serial for a card.
     */
    public function serialFor(Card $card): string;

    /**
     * Push the current card state to an already-issued pass (live update).
     * No-op when the provider or live push is not configured.
     */
    public function pushUpdate(Card $card): void;
}
