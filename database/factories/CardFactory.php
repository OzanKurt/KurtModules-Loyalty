<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'token' => bin2hex(random_bytes(6)),
        ];
    }
}
