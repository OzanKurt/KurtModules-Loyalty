<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Tests;

use Illuminate\Foundation\Application;

abstract class PushTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('loyalty.wallet.push', true);
    }
}
