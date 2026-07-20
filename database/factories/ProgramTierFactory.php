<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\ProgramTier;

/**
 * @extends Factory<ProgramTier>
 */
class ProgramTierFactory extends Factory
{
    protected $model = ProgramTier::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'threshold' => 5,
            'reward' => ['en' => 'A free drink'],
            'reward_payload' => null,
            'position' => 0,
        ];
    }
}
