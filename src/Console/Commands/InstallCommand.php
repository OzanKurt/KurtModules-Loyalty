<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Loyalty\Providers\LoyaltyServiceProvider;

final class InstallCommand extends Command
{
    protected $signature = 'loyalty:install {--force : Overwrite existing published files}';

    protected $description = 'Publish the loyalty config, migrations, views and compiled assets.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--provider' => LoyaltyServiceProvider::class,
            '--force' => (bool) $this->option('force'),
        ]);

        $this->info('Loyalty assets published. Run `php artisan migrate` to create the tables.');

        return self::SUCCESS;
    }
}
