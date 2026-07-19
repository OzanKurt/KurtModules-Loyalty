<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Providers;

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Loyalty\Console\Commands\DemoCommand;
use Kurt\Modules\Loyalty\Console\Commands\InstallCommand;
use Kurt\Modules\Loyalty\Console\Commands\PruneVouchersCommand;
use Kurt\Modules\Loyalty\Console\Commands\WalletCheckCommand;
use Kurt\Modules\Loyalty\Events\CardCompleted;
use Kurt\Modules\Loyalty\Events\RewardRedeemed;
use Kurt\Modules\Loyalty\Events\StampAdded;
use Kurt\Modules\Loyalty\Wallet\WalletManager;
use Spatie\LaravelPackageTools\Package;

final class LoyaltyServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'loyalty';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-loyalty')
            ->hasConfigFile('loyalty')
            ->hasTranslations()
            ->hasViews('loyalty')
            ->hasAssets()
            ->hasRoute('loyalty')
            ->hasMigrations([
                'create_loyalty_programs_table',
                'create_loyalty_cards_table',
                'create_loyalty_vouchers_table',
                'create_loyalty_stamps_table',
                'create_loyalty_redemptions_table',
                'create_loyalty_wallet_passes_table',
                'create_loyalty_wallet_registrations_table',
            ])
            ->hasCommands([
                InstallCommand::class,
                DemoCommand::class,
                PruneVouchersCommand::class,
                WalletCheckCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WalletManager::class);
    }

    public function packageBooted(): void
    {
        $this->registerWalletPush();
    }

    /**
     * Wire live wallet updates: on any card-changing event, push the current
     * state to issued passes. Opt-in via config; providers no-op until their
     * credentials + push infrastructure are configured.
     */
    private function registerWalletPush(): void
    {
        if (! (bool) config('loyalty.wallet.push', false)) {
            return;
        }

        Event::listen(
            [StampAdded::class, CardCompleted::class, RewardRedeemed::class],
            function (StampAdded|CardCompleted|RewardRedeemed $event): void {
                $wallet = $this->app->make(WalletManager::class);

                if ($wallet->appleEnabled()) {
                    $wallet->apple()->pushUpdate($event->card);
                }
                if ($wallet->googleEnabled()) {
                    $wallet->google()->pushUpdate($event->card);
                }
            },
        );
    }
}
