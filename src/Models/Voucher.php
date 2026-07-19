<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Database\Factories\Kurt\Modules\Loyalty\VoucherFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $table = 'loyalty_vouchers';

    protected $guarded = [];

    protected $casts = [
        'stamps' => 'integer',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Card, $this> */
    public function redeemedByCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'redeemed_by_card_id');
    }

    public function isRedeemable(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    protected static function newFactory(): Factory
    {
        return VoucherFactory::new();
    }
}
