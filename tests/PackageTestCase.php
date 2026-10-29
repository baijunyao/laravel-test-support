<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Tests;

use Baijunyao\LaravelTestSupport\TestCase;
use Orchestra\Testbench\Foundation\Application;

abstract class PackageTestCase extends TestCase
{
    public function createApplication()
    {
        $app = Application::create();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.mysql.prefix', '');

        return $app;
    }
}
