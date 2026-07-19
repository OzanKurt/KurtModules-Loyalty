<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Loyalty;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\Voucher;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'token' => bin2hex(random_bytes(20)),
            'stamps' => 1,
            'status' => 'pending',
        ];
    }
}
