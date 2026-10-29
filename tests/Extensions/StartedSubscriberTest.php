<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Tests\Extensions;

use Baijunyao\LaravelTestSupport\Extensions\StartedSubscriber;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Date;
use Orchestra\Testbench\TestCase;
use PHPUnit\Event\TestRunner\Started;
use ReflectionClass;

final class StartedSubscriberTest extends TestCase
{
    public function testOldDatabasesAreRemovedWithSignedCarbonDifferences(): void
    {
        Date::setTestNow('2026-10-29 12:00:00');
        $originalDatabase = $_SERVER['DB_DATABASE'] ?? null;
        $schema = $this->createMock(Builder::class);
        $schema->expects($this->once())->method('createDatabase')->with($this->matchesRegularExpression('/^test_\d{8}_/'));
        $schema->expects($this->once())->method('dropDatabaseIfExists')->with('test_20261028_old');
        $manager = $this->getMockBuilder(DatabaseManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connection', 'usingConnection'])
            ->getMock();
        $connection = $this->createMock(Connection::class);
        $connection->method('getSchemaBuilder')->willReturn($schema);
        $manager->method('connection')->willReturn($connection);
        $manager->expects($this->once())->method('usingConnection')->with('testing', $this->isCallable());
        $connection->expects($this->once())->method('select')->willReturn([
            (object) ['database' => 'test_20261028_old'],
            (object) ['database' => 'test_20261029_recent'],
            (object) ['database' => 'test_invalid_name'],
        ]);
        $this->app->instance('db', $manager);
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql', ['driver' => 'mysql', 'database' => 'source']);

        try {
            (new StartedSubscriber($this->app, $this->app->basePath() . '/', 'test_'))->notify(
                (new ReflectionClass(Started::class))->newInstanceWithoutConstructor()
            );
            $testingConfig = $this->app['config']->get('database.connections.testing');
            $this->assertSame('mysql', $testingConfig['driver']);
            $this->assertSame($_SERVER['DB_DATABASE'], $testingConfig['database']);
        } finally {
            Date::setTestNow();
            if ($originalDatabase === null) {
                unset($_SERVER['DB_DATABASE']);
            } else {
                $_SERVER['DB_DATABASE'] = $originalDatabase;
            }
        }
    }
}
