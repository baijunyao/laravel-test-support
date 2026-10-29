<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Tests\Extensions;

use Baijunyao\LaravelTestSupport\Extensions\FinishedSubscriber;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Orchestra\Testbench\TestCase;
use PHPUnit\Event\TestRunner\Finished;
use ReflectionClass;

final class FinishedSubscriberTest extends TestCase
{
    public function testPostgresSessionsAreTerminatedBeforeTheTestDatabaseIsDropped(): void
    {
        $database = 'test_20261029_owned';
        $this->app['config']->set('database.connections.testing', ['driver' => 'pgsql', 'database' => $database]);
        $terminated = false;
        $schema = $this->createMock(Builder::class);
        $schema->expects($this->once())->method('dropDatabaseIfExists')->with($database)
            ->willReturnCallback(function () use (&$terminated): bool {
                $this->assertTrue($terminated);

                return true;
            });
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('statement')->with(
            'SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = ? AND pid != pg_backend_pid();',
            [$database]
        )->willReturnCallback(function () use (&$terminated): bool {
            $terminated = true;

            return true;
        });
        $connection->method('getSchemaBuilder')->willReturn($schema);
        $this->app->instance('db', $connection);

        (new FinishedSubscriber($this->app, 'test_'))->notify(
            (new ReflectionClass(Finished::class))->newInstanceWithoutConstructor()
        );
    }
}
