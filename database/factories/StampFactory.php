<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Stamp;

/**
 * @extends Factory<Stamp>
 */
class StampFactory extends Factory
{
    protected $model = Stamp::class;

    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'source' => StampSource::Manual->value,
            'created_at' => now(),
        ];
    }
}
