<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\WalletPass;
use Kurt\Modules\Loyalty\Models\WalletRegistration;
use Kurt\Modules\Loyalty\Wallet\WalletManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Implements the Apple Wallet web service (PassKit) used for live pass updates.
 * Auth is the `Authorization: ApplePass <token>` header matched against the
 * pass's stored auth token.
 */
class AppleWebServiceController extends Controller
{
    public function register(Request $request, string $device, string $passType, string $serial): Response
    {
        $this->authenticate($request, $serial);

        WalletRegistration::query()->firstOrCreate(
            ['device_library_id' => $device, 'pass_serial' => $serial],
            ['push_token' => (string) $request->input('pushToken')],
        );

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function unregister(Request $request, string $device, string $passType, string $serial): Response
    {
        $this->authenticate($request, $serial);

        WalletRegistration::query()
            ->where('device_library_id', $device)
            ->where('pass_serial', $serial)
            ->delete();

        return response()->noContent();
    }

    public function serials(Request $request, string $device, string $passType): Response|JsonResponse
    {
        $serials = WalletRegistration::query()
            ->where('device_library_id', $device)
            ->pluck('pass_serial')
            ->unique()
            ->values();

        if ($serials->isEmpty()) {
            return response()->noContent();
        }

        return response()->json([
            'lastUpdated' => (string) now()->timestamp,
            'serialNumbers' => $serials,
        ]);
    }

    public function pass(Request $request, string $passType, string $serial, WalletManager $wallet): Response
    {
        $pass = $this->authenticate($request, $serial);
        $card = Card::query()->where('token', $serial)->firstOrFail();

        return response($wallet->apple()->pkpass($card, $wallet->appleWebServiceUrl(), (string) $pass->auth_token))
            ->header('Content-Type', 'application/vnd.apple.pkpass');
    }

    public function log(Request $request): Response
    {
        return response()->noContent();
    }

    private function authenticate(Request $request, string $serial): WalletPass
    {
        $token = trim(str_ireplace('ApplePass', '', (string) $request->header('Authorization')));

        $pass = WalletPass::query()
            ->where('platform', 'apple')
            ->where('external_id', $serial)
            ->first();

        if ($pass === null || $pass->auth_token === null || ! hash_equals($pass->auth_token, $token)) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid pass authentication token.');
        }

        return $pass;
    }
}
