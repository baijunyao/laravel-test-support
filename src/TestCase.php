<?php

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use ReseedsTestDatabase;

    protected static $databaseNeedInit = true;
    protected static $bootstrappers = [];

    public function setUp(): void
    {
        parent::setUp();

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
