<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use AssertsBaseline, ReseedsTestDatabase;

    protected static $databaseNeedInit = true;
    protected static $bootstrappers    = [];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2019));

        if (static::$databaseNeedInit) {
            $this->artisan('migrate');
            $this->artisan('db:seed');
            static::$databaseNeedInit = false;

            foreach (static::$bootstrappers as $bootstrapper) {
                (new $bootstrapper($this->app))->boot();
            }
        }

        static::registerDatabaseListener();
        static::reseed();
    }
}
