<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Wallet;

use Illuminate\Contracts\Config\Repository;

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
}
