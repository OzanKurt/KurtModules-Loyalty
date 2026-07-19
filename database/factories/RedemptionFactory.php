<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Redemption;

/**
 * @extends Factory<Redemption>
 */
class RedemptionFactory extends Factory
{
    protected $model = Redemption::class;

    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'reward' => ['en' => 'A free drink'],
            'created_at' => now(),
        ];
    }
}
