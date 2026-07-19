<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Loyalty\Enums\WalletPlatform;

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
