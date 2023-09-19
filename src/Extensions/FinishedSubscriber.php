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
        $database = $_SERVER['DB_DATABASE'];

        if (Str::startsWith($database, $this->databasePrefix) === false) {
            echo PHP_EOL . 'Database error.' . PHP_EOL;

            exit(Result::FAILURE);
        }

        try {
            $this->laravel['db']->getSchemaBuilder()->dropDatabaseIfExists($database);
        } catch (Throwable $exception) {
            echo PHP_EOL . 'Drop database error: ' . $exception->getMessage() . PHP_EOL;

            exit(Result::FAILURE);
        }
    }
}
