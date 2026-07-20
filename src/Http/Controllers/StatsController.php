<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\LoyaltyStatsService;

class StatsController extends ApiController
{
    public function index(Request $request, LoyaltyStatsService $stats): JsonResponse
    {
        $data = $request->validate([
            'program' => ['nullable', 'string'],
            'since' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $programId = null;
        if (! empty($data['program'])) {
            $value = $data['program'];
            $program = Program::query()
                ->where('slug', $value)
                ->when(ctype_digit($value), fn ($q) => $q->orWhere('id', (int) $value))
                ->first();

            if ($program === null) {
                return $this->fail('Program not found.', 404);
            }

            $programId = (int) $program->getKey();
        }

        return $this->respond($stats->overview(
            $programId,
            isset($data['since']) ? Carbon::parse($data['since']) : null,
            isset($data['until']) ? Carbon::parse($data['until']) : null,
        ));
    }
}
