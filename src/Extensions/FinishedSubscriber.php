<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Extensions;

use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\TextUI\Command\Result;
use Throwable;

final class FinishedSubscriber implements \PHPUnit\Event\TestRunner\FinishedSubscriber
{
    public function __construct(private Application $laravel, private string $databasePrefix)
    {
    }

    public function notify(Finished $event): void
    {
        $databaseConfig = $this->laravel['config']->get('database.connections.testing');

        if ($databaseConfig === null) {
            echo PHP_EOL . 'Config error.' . PHP_EOL;

            exit(Result::FAILURE);
        }

        $database = $databaseConfig['database'];

        if (Str::startsWith($database, $this->databasePrefix) === false) {
            echo PHP_EOL . 'Database error.' . PHP_EOL;

            exit(Result::FAILURE);
        }

        try {
            if ($databaseConfig['driver'] === 'pgsql') {
                $this->laravel['db']->statement(
                    'SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = ? AND pid != pg_backend_pid();',
                    [$database]
                );
            }

            $this->laravel['db']->getSchemaBuilder()->dropDatabaseIfExists($database);
        } catch (Throwable $exception) {
            echo PHP_EOL . 'Drop database error: ' . $exception->getMessage() . PHP_EOL;

            exit(Result::FAILURE);
        }
    }
}
