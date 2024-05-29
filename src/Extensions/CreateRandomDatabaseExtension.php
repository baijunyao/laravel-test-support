<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Extensions;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class CreateRandomDatabaseExtension implements Extension
{
    private const DATABASE_PREFIX = 'test_';

    private DatabaseManager $databaseManager;
    private string $databaseName;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $rootPath      = getcwd() . '/';
        $envRepository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();
        Dotenv::create($envRepository, $rootPath, '.env')->safeLoad();

        $laravel = require $rootPath . 'bootstrap/app.php';
        $laravel->make(Kernel::class)->bootstrap();

        $facade->registerSubscribers(
            new StartedSubscriber($laravel, $rootPath, static::DATABASE_PREFIX),
            new FinishedSubscriber($laravel, static::DATABASE_PREFIX),
        );
    }
}
