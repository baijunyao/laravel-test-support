<?php

namespace Baijunyao\LaravelTestSupport;

use Illuminate\Support\Str;

trait ReseedsTestDatabase
{
    protected static $dirtyTables = [];

    protected function reseed()
    {
        foreach (static::$dirtyTables as $dirtyTable) {
            $this->artisan('db:seed', [
                '--class' => Str::studly(str_replace(config('database.connections.mysql.prefix'), '', $dirtyTable)) . 'TableSeeder'
            ]);
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
