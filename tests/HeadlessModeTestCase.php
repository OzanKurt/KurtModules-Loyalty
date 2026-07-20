<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Tests;

use Illuminate\Foundation\Application;

abstract class HeadlessModeTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('loyalty.http.mode', 'headless');
    }
}
