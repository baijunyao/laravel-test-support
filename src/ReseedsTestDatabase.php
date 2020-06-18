<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait ReseedsTestDatabase
{
    protected static $dirtyTables = [];

    protected function reseed()
    {
        foreach (static::$dirtyTables as $dirtyTable) {
            $tableNameWithoutPrefix = str_replace(config('database.connections.mysql.prefix'), '', $dirtyTable);
            $seeder                 = Str::studly($tableNameWithoutPrefix) . 'TableSeeder';

            if (file_exists(database_path("seeds/{$seeder}.php"))) {
                DB::table($tableNameWithoutPrefix)->truncate();

                $this->artisan('db:seed', [
                    '--class' => $seeder,
                ]);
            }
        }

        static::$dirtyTables = [];
    }

    protected static function registerDatabaseListener()
    {
        app('db')->listen(function ($query) {
            preg_match_all('/^(?:insert|update|delete).+?`(.+?)`.*/', $query->sql, $table);

            if (!empty($table[1][0])) {
                static::$dirtyTables[] = $table[1][0];
            }
        });
    }
}
