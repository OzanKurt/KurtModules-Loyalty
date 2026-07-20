<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Wallet\WalletManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WalletController extends Controller
{
    public function apple(string $token, WalletManager $wallet): Response
    {
        if (! $wallet->appleEnabled()) {
            throw new NotFoundHttpException('Apple Wallet is not enabled.');
        }

        $card = $this->resolveCard($token);

        // When live push is enabled, embed the web-service URL + this pass's
        // auth token so the device can register for updates.
        $pass = $wallet->ensureApplePass($card);
        $webServiceUrl = $wallet->pushEnabled() ? $wallet->appleWebServiceUrl() : null;
        $authToken = $wallet->pushEnabled() ? $pass->auth_token : null;

        return response($wallet->apple()->pkpass($card, $webServiceUrl, $authToken))
            ->header('Content-Type', 'application/vnd.apple.pkpass')
            ->header('Content-Disposition', 'attachment; filename="'.$card->code.'.pkpass"');
    }

    public function google(string $token, WalletManager $wallet): RedirectResponse
    {
        if (! $wallet->googleEnabled()) {
            throw new NotFoundHttpException('Google Wallet is not enabled.');
        }

        return redirect()->away($wallet->google()->saveUrl($this->resolveCard($token)));
    }

    private function resolveCard(string $token): Card
    {
        return Card::query()->where('token', $token)->firstOrFail();
    }
}
