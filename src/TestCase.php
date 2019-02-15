<?php

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use ReseedsTestDatabase;

    protected static $databaseNeedInit = true;

    public function setUp()
    {
        parent::setUp();

        if (static::$databaseNeedInit) {
            $this->artisan('migrate');
            $this->artisan('db:seed');
            static::$databaseNeedInit = false;
        }

        static::registerDatabaseListener();
        static::reseed();
    }
}
