<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Exceptions\DailyStampLimitReachedException;
use Kurt\Modules\Loyalty\Exceptions\NoRewardAvailableException;
use Kurt\Modules\Loyalty\Exceptions\StampThrottledException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Services\RedemptionService;
use Kurt\Modules\Loyalty\Services\StampService;
use Kurt\Modules\Loyalty\Support\CardState;
use Kurt\Modules\Loyalty\Support\Idempotency;

class TerminalController extends Controller
{
    public function index(): View
    {
        return view('loyalty::terminal');
    }

    public function stamp(Request $request, StampService $stamps): JsonResponse
    {
        $card = $this->resolveCard($request);

        if ($this->isReplay($request)) {
            return response()->json(CardState::for($card));
        }

        try {
            $card = $stamps->add($card, StampSource::StaffTerminal, grantedBy: $this->actor($request));
        } catch (StampThrottledException|DailyStampLimitReachedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(CardState::for($card));
    }

    public function redeem(Request $request, RedemptionService $redemptions): JsonResponse
    {
        $card = $this->resolveCard($request);

        if ($this->isReplay($request)) {
            return response()->json(CardState::for($card));
        }

        try {
            $card = $redemptions->redeem($card, redeemedBy: $this->actor($request));
        } catch (NoRewardAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(CardState::for($card));
    }

    /**
     * True when the request carries an idempotency key already seen within the
     * TTL — a double-tap / retry that must not re-apply the action.
     */
    private function isReplay(Request $request): bool
    {
        $key = Idempotency::key($request);

        return $key !== null && ! Idempotency::claim($key);
    }

    private function resolveCard(Request $request): Card
    {
        $data = $request->validate(['card_token' => ['required', 'string']]);
        $value = trim($data['card_token']);

        // The terminal is staff-gated, so it may resolve a card by either the
        // scanned/typed short code or the long URL token.
        return Card::query()
            ->where('code', strtoupper($value))
            ->orWhere('token', $value)
            ->firstOrFail();
    }

    private function actor(Request $request): ?string
    {
        $id = $request->user()?->getAuthIdentifier();

        return $id === null ? null : (string) $id;
    }
}
