<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\LoyaltyStatsService;

class StatsController extends Controller
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
                return response()->json(['message' => 'Program not found.'], 404);
            }

            $programId = (int) $program->getKey();
        }

        return response()->json($stats->overview(
            $programId,
            isset($data['since']) ? Carbon::parse($data['since']) : null,
            isset($data['until']) ? Carbon::parse($data['until']) : null,
        ));
    }
}
