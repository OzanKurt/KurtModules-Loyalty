<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Database\Factories\Kurt\Modules\Loyalty\ProgramTierFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $program_id
 * @property int $threshold
 * @property string $reward
 * @property array<string, mixed>|null $reward_payload
 * @property int $position
 * @property Program $program
 */
class ProgramTier extends Model
{
    /** @use HasFactory<ProgramTierFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $table = 'loyalty_program_tiers';

    protected $guarded = [];

    /** @var array<int, string> */
    public array $translatable = ['reward'];

    protected $casts = [
        'threshold' => 'integer',
        'reward_payload' => 'array',
        'position' => 'integer',
    ];

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return ProgramTierFactory */
    protected static function newFactory(): Factory
    {
        return ProgramTierFactory::new();
    }
}
