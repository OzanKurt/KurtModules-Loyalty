<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Database\Factories\Kurt\Modules\Loyalty\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $table = 'loyalty_programs';

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['name', 'reward'];

    protected $casts = [
        'stamps_required' => 'integer',
        'cooldown_seconds' => 'integer',
        'max_per_day' => 'integer',
        'reset_on_reward' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<Card, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /** @return HasMany<Voucher, $this> */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /** @return MorphTo<Model, $this> */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return ProgramFactory::new();
    }
}
