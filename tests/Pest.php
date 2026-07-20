<?php

declare(strict_types=1);
use Kurt\Modules\Loyalty\Tests\ApiModeTestCase;
use Kurt\Modules\Loyalty\Tests\HeadlessModeTestCase;
use Kurt\Modules\Loyalty\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(ApiModeTestCase::class)->in('Modes/Api');
uses(HeadlessModeTestCase::class)->in('Modes/Headless');
