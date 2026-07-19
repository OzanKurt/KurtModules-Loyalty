<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Loyalty\Models\Program;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ['en' => Str::title($name)],
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'reward' => ['en' => 'A free drink'],
            'stamps_required' => 10,
            'theme' => 'coffee',
            'icon' => 'coffee',
            'cooldown_seconds' => 30,
            'max_per_day' => null,
            'reset_on_reward' => true,
            'is_active' => true,
        ];
    }
}
