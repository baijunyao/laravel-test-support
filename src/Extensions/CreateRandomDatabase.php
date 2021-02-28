<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Extensions;

use Baijunyao\LaravelTestSupport\Exception\DatabaseException;
use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\Date;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

class CreateRandomDatabase implements BeforeFirstTestHook, AfterLastTestHook
{
    private const APP_ROOT_PATH        = __DIR__ . '/../../../../../';
    private const DATABASE_NAME_PREFIX = 'test_';

    private static ?MySqlConnection $root_connection = null;

    public function executeBeforeFirstTest(): void
    {
        $database               = static::DATABASE_NAME_PREFIX . date('Ymd') . '_' . uniqid();
        $_SERVER['DB_DATABASE'] = static::createTestDatabase($database);

        static::cleanUnusedDatabases();
    }

    public function executeAfterLastTest(): void
    {
        static::dropTestDatabase($_SERVER['DB_DATABASE']);
    }

    private static function createTestDatabase(string $database): string
    {
        if (static::getRootConnection()->getSchemaBuilder()->createDatabase($database) === false) {
            throw new DatabaseException('Create database failed.');
        }

        return $database;
    }

    private static function cleanUnusedDatabases(): void
    {
        $databases = static::getRootConnection()->select('SHOW DATABASES LIKE "' . static::DATABASE_NAME_PREFIX . '%"');

        foreach ($databases as $database) {
            /** @var string $databaseName */
            $databaseName = reset($database);

            $date = explode('_', $databaseName)[1] ?? null;

            if (Date::hasFormat($date, 'Ymd') && Date::now()->diffInHours(Date::createFromFormat('Ymd', $date)) >= 2) {
                static::dropTestDatabase($databaseName);
            }
        }
    }

    private static function dropTestDatabase(string $database): bool
    {
        return static::getRootConnection()->getSchemaBuilder()->dropDatabaseIfExists($database);
    }

    private static function getRootConnection(): MySqlConnection
    {
        if (static::$root_connection === null) {
            $env_repository = RepositoryBuilder::createWithNoAdapters()
                ->addAdapter(PutenvAdapter::class)
                ->immutable()
                ->make();
            Dotenv::create($env_repository, static::APP_ROOT_PATH, '.env')->safeLoad();

            $db_manager = new Manager();
            $db_manager->addConnection([
                'driver'      => 'mysql',
                'port'        => $env_repository->get('DB_PORT'),
                'host'        => $env_repository->get('DB_HOST'),
                'database'    => $env_repository->get('DB_DATABASE'),
                'username'    => $env_repository->get('DB_USERNAME'),
                'password'    => $env_repository->get('DB_PASSWORD'),
                'unix_socket' => $env_repository->get('DB_SOCKET'),
                'charset'     => 'utf8mb4',
                'collation'   => 'utf8mb4_unicode_ci',
                'prefix'      => '',
                'strict'      => true,
                'engine'      => null,
            ]);

            static::$root_connection = $db_manager->getConnection();
        }

        return static::$root_connection;
    }
}
