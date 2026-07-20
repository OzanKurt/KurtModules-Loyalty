<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Wallet;

use Illuminate\Contracts\Config\Repository;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\WalletPass;

final class WalletManager
{
    private AppleWalletProvider $apple;

    private GoogleWalletProvider $google;

    public function __construct(Repository $config)
    {
        /** @var array<string, mixed> $appleConfig */
        $appleConfig = (array) $config->get('loyalty.wallet.apple', []);
        /** @var array<string, mixed> $googleConfig */
        $googleConfig = (array) $config->get('loyalty.wallet.google', []);

        $this->apple = new AppleWalletProvider($appleConfig);
        $this->google = new GoogleWalletProvider($googleConfig);
    }

    public function apple(): AppleWalletProvider
    {
        return $this->apple;
    }

    public function google(): GoogleWalletProvider
    {
        return $this->google;
    }

    public function appleEnabled(): bool
    {
        return $this->apple->isConfigured();
    }

    public function googleEnabled(): bool
    {
        return $this->google->isConfigured();
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        $platforms = [];
        if ($this->appleEnabled()) {
            $platforms[] = 'apple';
        }
        if ($this->googleEnabled()) {
            $platforms[] = 'google';
        }

        return $platforms;
    }

    public function pushEnabled(): bool
    {
        return (bool) config('loyalty.wallet.push', false);
    }

    /**
     * Get-or-create the tracked Apple pass row for a card (serial + auth token).
     */
    public function ensureApplePass(Card $card): WalletPass
    {
        /** @var WalletPass $pass */
        $pass = WalletPass::query()->firstOrCreate(
            ['card_id' => $card->getKey(), 'platform' => 'apple'],
            ['external_id' => $this->apple->serialFor($card), 'auth_token' => bin2hex(random_bytes(16))],
        );

        return $pass;
    }

    /**
     * Absolute HTTPS base Apple Wallet devices call back to.
     */
    public function appleWebServiceUrl(): string
    {
        $configured = config('loyalty.wallet.apple.web_service_url');
        $base = is_string($configured) && $configured !== ''
            ? $configured
            : url((string) config('loyalty.routes.prefix', 'loyalty').'/apple');

        return rtrim($base, '/');
    }
}
