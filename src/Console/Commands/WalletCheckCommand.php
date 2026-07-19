<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Loyalty\Wallet\WalletManager;

final class WalletCheckCommand extends Command
{
    protected $signature = 'loyalty:wallet-check';

    protected $description = 'Report which wallet providers are configured.';

    public function handle(WalletManager $wallet): int
    {
        $this->table(['Provider', 'Configured'], [
            ['Apple Wallet', $wallet->appleEnabled() ? 'yes' : 'no'],
            ['Google Wallet', $wallet->googleEnabled() ? 'yes' : 'no'],
        ]);

        $available = $wallet->available();

        if ($available === []) {
            $this->warn('No wallet providers are configured. "Add to wallet" buttons will be hidden.');
        } else {
            $this->info('Enabled: '.implode(', ', $available));
        }

        return self::SUCCESS;
    }
}
