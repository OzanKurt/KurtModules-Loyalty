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

        return response($wallet->apple()->pkpass($card))
            ->header('Content-Type', 'application/vnd.apple.pkpass')
            ->header('Content-Disposition', 'attachment; filename="'.$card->token.'.pkpass"');
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
