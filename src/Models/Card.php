<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Database\Factories\Kurt\Modules\Loyalty\CardFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kurt\Modules\Loyalty\Enums\CardStatus;

class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'loyalty_cards';

    protected $guarded = [];

    protected $attributes = [
        'stamps_count' => 0,
        'rewards_earned' => 0,
        'rewards_redeemed' => 0,
        'status' => 'active',
    ];

    protected $casts = [
        'status' => CardStatus::class,
        'last_stamped_at' => 'datetime',
        'stamps_count' => 'integer',
        'rewards_earned' => 'integer',
        'rewards_redeemed' => 'integer',
    ];

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<Stamp, $this> */
    public function stamps(): HasMany
    {
        return $this->hasMany(Stamp::class);
    }

    /** @return HasMany<Redemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    /** @return HasMany<WalletPass, $this> */
    public function walletPasses(): HasMany
    {
        return $this->hasMany(WalletPass::class);
    }

    public function isComplete(): bool
    {
        return $this->stamps_count >= $this->program->stamps_required;
    }

    public function rewardsAvailable(): int
    {
        return max(0, $this->rewards_earned - $this->rewards_redeemed);
    }

    protected static function newFactory(): Factory
    {
        return CardFactory::new();
    }
}
