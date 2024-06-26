<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Extensions;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Date;
use PHPUnit\Event\TestRunner\Started;
use PHPUnit\TextUI\Command\Result;
use Throwable;

final class StartedSubscriber implements \PHPUnit\Event\TestRunner\StartedSubscriber
{
    private string $databaseName;
    private DatabaseManager $databaseManager;

    public function __construct(private Application $laravel, private string $appRootPath, private string $databasePrefix)
    {
    }

    public function notify(Started $event): void
    {
        try {
            // Create test database
            $this->databaseManager = $this->laravel['db'];
            $this->databaseName    = $this->databasePrefix . date('Ymd') . '_' . uniqid();
            $this->databaseManager->getSchemaBuilder()->createDatabase($this->databaseName);
            $_SERVER['DB_DATABASE'] = $this->databaseName;

            // Set database config
            /** @var \Illuminate\Config\Repository $configRepository */
            $configRepository = $this->laravel['config'];
            $configRepository->set(
                'database.connections.testing',
                ['database' => $this->databaseName] + $configRepository->get('database.connections.' . $configRepository->get('database.default'))
            );

            // Create tables and seed test data
            $this->databaseManager->usingConnection('testing', function () {
                // Migration
                /** @var \Illuminate\Database\Migrations\Migrator $migrator */
                $migrator = $this->laravel['migrator'];
                $migrator->getRepository()->createRepository();
                $migrator->run($this->appRootPath . 'database/migrations');

                Carbon::setTestNow(Carbon::create(2019));

                // Seed
                $this->seed();

                Carbon::setTestNow();
            });

            $driver = $configRepository->get('database.connections.testing.driver');

            if ($driver === 'pgsql') {
                $showDatabaseSql = "SELECT datname FROM pg_database WHERE datname like '" . $this->databasePrefix . "%'";
            } else {
                $showDatabaseSql = 'SHOW DATABASES LIKE "' . $this->databasePrefix . '%"';
            }

            // Clean unused databases
            $databases = $this->databaseManager->select($showDatabaseSql);

            foreach ($databases as $database) {
                /** @var string $databaseName */
                $databaseName = reset($database);

                $date = explode('_', $databaseName)[1] ?? null;

                if (Date::hasFormat($date, 'Ymd') && Date::now()->diffInHours(Date::createFromFormat('Ymd', $date)) >= 2) {
                    $this->databaseManager->getSchemaBuilder()->dropDatabaseIfExists($databaseName);
                }
            }
        } catch (Throwable $exception) {
            echo PHP_EOL . 'Create database error: ' . $exception->getMessage() . PHP_EOL;

            exit(Result::FAILURE);
        }
    }

    protected function seed()
    {
        Carbon::setTestNow(Carbon::create(2019));

        $databaseSeeder = 'Database\\Seeders\\DatabaseSeeder';

        if (class_exists($databaseSeeder)) {
            (new $databaseSeeder())->run();
        }

        Carbon::setTestNow();
    }
}
