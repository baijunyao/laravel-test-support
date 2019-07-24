<?php

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Support\Str;

trait ReseedsTestDatabase
{
    protected static $dirtyTables = [];

    protected function reseed()
    {
        foreach (static::$dirtyTables as $dirtyTable) {
            $seeder = Str::studly(str_replace(config('database.connections.mysql.prefix'), '', $dirtyTable)) . 'TableSeeder';

            if (file_exists(database_path("seeds/$seeder.php"))) {
                $this->artisan('db:seed', [
                    '--class' => $seeder
                ]);
            }
        }

        static::$dirtyTables = [];
    }

    protected static function registerDatabaseListener()
    {
        app('db')->listen(function ($query) {
            preg_match_all('/^(?:insert into|update|delete from) `(.+?)`.*/', $query->sql, $table);

            if (!empty($table[1][0])) {
                static::$dirtyTables[] = $table[1][0];
            }
        });
    }
}
