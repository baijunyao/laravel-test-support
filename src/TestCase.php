<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use ReflectionClass;
use Spatie\Snapshots\MatchesSnapshots;

abstract class TestCase extends BaseTestCase
{
    use MatchesSnapshots;

    public array $parameter = [];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2019));

        DB::enableQueryLog();
    }

    protected function tearDown(): void
    {
        $dirtyTables = [];

        foreach (DB::getQueryLog() as $queryLog) {
            preg_match_all('/^(?:insert|update|delete).+?[`\'"](.+?)[`\'"].*/', $queryLog['query'], $table);

            if (isset($table[1][0]) === true && in_array($table[1][0], $dirtyTables, true) === false) {
                $dirtyTables[] = $table[1][0];
            }
        }

        if ($dirtyTables !== []) {
            Schema::disableForeignKeyConstraints();

            try {
                foreach (array_unique($dirtyTables) as $dirtyTable) {
                    $tableNameWithoutPrefix = str_replace(config('database.connections.mysql.prefix'), '', $dirtyTable);

                    /**
                     * There are two possibilities, e.g.
                     * 1. The class name before Laravel 8 is UsersTableSeeder, path is database/seeds/UsersTableSeeder.php
                     * 2. The class name after Laravel 8 is UserSeeder, path is database/seeders/UserSeeder.php
                     */
                    $seederBeforeLaravel8 = Str::studly($tableNameWithoutPrefix) . 'TableSeeder';
                    $pathBeforeLaravel8   = database_path("seeds/{$seederBeforeLaravel8}.php");

                    $seederAfterLaravel8 = Str::singular(Str::studly($tableNameWithoutPrefix)) . 'Seeder';
                    $pathAfterLaravel8   = database_path("seeders/{$seederAfterLaravel8}.php");

                    $isBeforeLaravel8 = File::exists($pathBeforeLaravel8);
                    $isAfterLaravel8  = File::exists($pathAfterLaravel8);

                    // Tables without a matching seeder must also be reset to prevent state leaking between tests.
                    DB::table($tableNameWithoutPrefix)->truncate();

                    if ($isBeforeLaravel8 === false && $isAfterLaravel8 === false) {
                        continue;
                    }

                    $seeder = $isBeforeLaravel8 === true ? $seederBeforeLaravel8 : $seederAfterLaravel8;

                    $this->artisan('db:seed', [
                        '--class' => $seeder,
                    ]);
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        }

        parent::tearDown();
    }

    public function assertResponse(TestResponse $response, array $contentIgnores = [])
    {
        $statusCode = $response->getStatusCode();
        $content    = preg_replace(array_keys($contentIgnores), array_values($contentIgnores), $response->getContent());

        if (Str::isJson($content)) {
            $content = json_decode($content, true);
        } else {
            if ($statusCode !== 200) {
                $content = (string) $response->baseResponse->exception;
            }
        }

        $this->assertMatchesJsonSnapshot([
            'status_code' => $response->getStatusCode(),
            'headers'     => array_merge($response->headers->all(), ['date' => Carbon::now()->format('D, d M Y H:i:s T')]),
            'content'     => $content,
        ]);
    }

    public function getRoute()
    {
        return Str::plural(
            Str::camel(
                str_replace('ControllerTest', '', class_basename(static::class))
            )
        );
    }

    public function getHeaders(): array
    {
        return [];
    }

    protected function getJsonEncodeFlags(): int
    {
        return JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
    }

    protected function getSnapshotDirectory(): string
    {
        return dirname(
            str_replace(
                $this->app->basePath('tests/'),
                $this->app->basePath('tests/_baseline/'),
                (new ReflectionClass($this))->getFileName()
            )
        );
    }
}
