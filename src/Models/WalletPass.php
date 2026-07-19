<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Loyalty\Enums\WalletPlatform;

/**
 * @property int $id
 * @property int $card_id
 * @property WalletPlatform $platform
 * @property string|null $external_id
 * @property string|null $auth_token
 * @property Carbon|null $last_pushed_at
 * @property Card $card
 */
class WalletPass extends Model
{
    protected $table = 'loyalty_wallet_passes';

    protected $guarded = [];

    protected $casts = [
        'platform' => WalletPlatform::class,
        'last_pushed_at' => 'datetime',
    ];

    /** @return BelongsTo<Card, $this> */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
