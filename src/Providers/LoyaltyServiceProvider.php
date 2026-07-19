<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Providers;

use Kurt\Modules\Core\Providers\PackageServiceProvider;
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
            ]);
    }
}
