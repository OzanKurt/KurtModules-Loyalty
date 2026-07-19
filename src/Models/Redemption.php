<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Database\Factories\Kurt\Modules\Loyalty\RedemptionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $card_id
 * @property array<string, string> $reward
 * @property string|null $redeemed_by
 * @property Carbon|null $created_at
 * @property Card $card
 */
class Redemption extends Model
{
    /** @use HasFactory<RedemptionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'loyalty_redemptions';

    protected $guarded = [];

    protected $casts = [
        'reward' => 'array',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Card, $this> */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /** @return RedemptionFactory */
    protected static function newFactory(): Factory
    {
        return RedemptionFactory::new();
    }
}
