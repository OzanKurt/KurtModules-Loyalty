<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Database\Factories\Kurt\Modules\Loyalty\StampFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kurt\Modules\Loyalty\Enums\StampSource;

class Stamp extends Model
{
    /** @use HasFactory<StampFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'loyalty_stamps';

    protected $guarded = [];

    protected $casts = [
        'source' => StampSource::class,
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Card, $this> */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    protected static function newFactory(): Factory
    {
        return StampFactory::new();
    }
}
