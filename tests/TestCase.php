<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Tests;

use Illuminate\Foundation\Application;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Kurt\Modules\Loyalty\Providers\LoyaltyServiceProvider;

abstract class TestCase extends PackageTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function modulePackageProviders($app): array
    {
        return [LoyaltyServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // The `web` middleware group (encrypt cookies / session) needs an app key.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
