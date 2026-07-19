<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kurt\Modules\Loyalty\Exceptions\CardNotClaimableException;
use Kurt\Modules\Loyalty\Exceptions\VoucherAlreadyRedeemedException;
use Kurt\Modules\Loyalty\Exceptions\VoucherExpiredException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\Voucher;
use Kurt\Modules\Loyalty\Services\CardService;
use Kurt\Modules\Loyalty\Services\VoucherService;
use Kurt\Modules\Loyalty\Support\CardState;

class CardController extends Controller
{
    public function show(string $token): View
    {
        $card = $this->resolveCard($token);

        return view('loyalty::card', ['state' => CardState::for($card)]);
    }

    public function state(string $token): JsonResponse
    {
        return response()->json(CardState::for($this->resolveCard($token)));
    }

    public function store(Request $request, Program $program, CardService $cards): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $card = $cards->create($program, $data);

        return response()->json(CardState::for($card), 201);
    }

    public function claim(Request $request, string $token, CardService $cards): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $card = $cards->claim($this->resolveCard($token), $data);
        } catch (CardNotClaimableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(CardState::for($card));
    }

    public function redeemVoucher(string $token, string $voucher, VoucherService $vouchers): JsonResponse
    {
        $card = $this->resolveCard($token);
        $voucherModel = Voucher::query()->where('token', $voucher)->firstOrFail();

        try {
            $card = $vouchers->redeem($voucherModel, $card);
        } catch (VoucherAlreadyRedeemedException|VoucherExpiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(CardState::for($card));
    }

    private function resolveCard(string $token): Card
    {
        return Card::query()->where('token', $token)->firstOrFail();
    }
}
