<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Extensions;

use Database\Seeders\DatabaseSeeder;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Date;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

class CreateRandomDatabase implements BeforeFirstTestHook, AfterLastTestHook
{
    private const APP_ROOT_PATH        = __DIR__ . '/../../../../../';
    private const DATABASE_NAME_PREFIX = 'test_';

    private Application $laravel;
    private DatabaseManager $databaseManager;
    private string $databaseName;

    public function executeBeforeFirstTest(): void
    {
        $env_repository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();
        Dotenv::create($env_repository, static::APP_ROOT_PATH, '.env')->safeLoad();

        $this->laravel = require static::APP_ROOT_PATH . 'bootstrap/app.php';
        $this->laravel->make(Kernel::class)->bootstrap();

        // Create test database
        /** @var \Illuminate\Database\DatabaseManager $databaseManager */
        $this->databaseManager = $this->laravel['db'];
        $this->databaseName    = static::DATABASE_NAME_PREFIX . date('Ymd') . '_' . uniqid();
        $this->databaseManager->getSchemaBuilder()->createDatabase($this->databaseName);
        $_SERVER['DB_DATABASE'] = $this->databaseName;

        // Set database config
        /** @var \Illuminate\Config\Repository $configRepository */
        $configRepository = $this->laravel['config'];
        $configRepository->set(
            'database.connections.testing',
            ['database' => $this->databaseName] + $configRepository->get('database.connections.mysql')
        );

        // Create tables and seed test data
        $this->databaseManager->usingConnection('testing', function () {
            // Migration
            /** @var \Illuminate\Database\Migrations\Migrator $migrator */
            $migrator = $this->laravel['migrator'];
            $migrator->getRepository()->createRepository();
            $migrator->run(static::APP_ROOT_PATH . 'database/migrations');

            // Seed
            (new DatabaseSeeder())->run();
        });

        // Clean unused databases
        $databases = $this->databaseManager->select('SHOW DATABASES LIKE "' . static::DATABASE_NAME_PREFIX . '%"');

        foreach ($databases as $database) {
            /** @var string $databaseName */
            $databaseName = reset($database);

            $date = explode('_', $databaseName)[1] ?? null;

            if (Date::hasFormat($date, 'Ymd') && Date::now()->diffInHours(Date::createFromFormat('Ymd', $date)) >= 2) {
                $this->databaseManager->getSchemaBuilder()->dropDatabaseIfExists($databaseName);
            }
        }
    }

    public function executeAfterLastTest(): void
    {
        $this->databaseManager->getSchemaBuilder()->dropDatabaseIfExists($this->databaseName);
    }
}
