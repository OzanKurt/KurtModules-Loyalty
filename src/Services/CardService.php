<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Services;

use Kurt\Modules\Loyalty\Enums\IdentityMode;
use Kurt\Modules\Loyalty\Events\CardClaimed;
use Kurt\Modules\Loyalty\Events\CardCreated;
use Kurt\Modules\Loyalty\Exceptions\CardNotClaimableException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

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

        $card->fill(array_intersect_key($identity, array_flip(['user_id', 'email', 'phone'])))->save();

        event(new CardClaimed($card));

        return $card->refresh();
    }

    private function mode(): IdentityMode
    {
        return IdentityMode::from((string) config('loyalty.identity_mode'));
    }

    private function uniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(6));
        } while (Card::query()->where('token', $token)->exists());

        return $token;
    }
}
