<?php

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use ReseedsTestDatabase;

    protected static $databaseNeedInit = true;
    protected static $databaseNeedDeleted = true;

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

    public function __destruct()
    {
        if (static::$databaseNeedDeleted) {
            Database::dropDatabase();
            static::$databaseNeedDeleted = false;
        }
    }
}
