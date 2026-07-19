<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Kurt\Modules\Loyalty\Models\Card;

trait IsLoyaltyCustomer
{
    /** @return HasMany<Card, $this> */
    public function loyaltyCards(): HasMany
    {
        return $this->hasMany(Card::class, 'user_id');
    }
}
