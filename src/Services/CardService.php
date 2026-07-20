<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Services;

use Kurt\Modules\Loyalty\Enums\IdentityMode;
use Kurt\Modules\Loyalty\Events\CardClaimed;
use Kurt\Modules\Loyalty\Events\CardCreated;
use Kurt\Modules\Loyalty\Exceptions\CardAlreadyClaimedException;
use Kurt\Modules\Loyalty\Exceptions\CardNotClaimableException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Support\CardCredentials;

final class CardService
{
    /**
     * @param  array{user_id?: int|null, email?: string|null, phone?: string|null}  $attributes
     */
    public function create(Program $program, array $attributes = []): Card
    {
        /** @var Card $card */
        $card = $program->cards()->create([
            'token' => $this->uniqueToken(),
            'code' => $this->uniqueCode(),
            'user_id' => $attributes['user_id'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
        ]);

        event(new CardCreated($card));

        return $card;
    }

    /**
     * @param  array{user_id?: int|null, email?: string|null, phone?: string|null}  $identity
     */
    public function claim(Card $card, array $identity): Card
    {
        if ($this->mode() === IdentityMode::Anonymous) {
            throw new CardNotClaimableException('Cards are anonymous in this install.');
        }

        if ($this->isClaimed($card)) {
            throw new CardAlreadyClaimedException('This card has already been claimed.');
        }

        $card->fill(array_intersect_key($identity, array_flip(['user_id', 'email', 'phone'])))->save();

        event(new CardClaimed($card));

        return $card->refresh();
    }

    private function isClaimed(Card $card): bool
    {
        return $card->user_id !== null || $card->email !== null || $card->phone !== null;
    }

    private function mode(): IdentityMode
    {
        return IdentityMode::from((string) config('loyalty.identity_mode'));
    }

    private function uniqueToken(): string
    {
        do {
            $token = CardCredentials::token();
        } while (Card::query()->where('token', $token)->exists());

        return $token;
    }

    private function uniqueCode(): string
    {
        do {
            $code = CardCredentials::code();
        } while (Card::query()->where('code', $code)->exists());

        return $code;
    }
}
