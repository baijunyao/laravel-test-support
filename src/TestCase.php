<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

            if (isset($table[1][0]) && !in_array($table[1][0], $dirtyTables, true)) {
                $dirtyTables[] = $table[1][0];
            }
        }

        foreach (array_unique($dirtyTables) as $dirtyTable) {
            $tableNameWithoutPrefix = str_replace(config('database.connections.mysql.prefix'), '', $dirtyTable);

            /**
             * There are two possibilities, e.g.
             * 1. The class name before Laravel 8 is UsersTableSeeder, path is database/seeds/UsersTableSeeder.php
             * 2. The class name after Laravel 8 is UserTableSeeder, path is database/seeders/UserTableSeeder.php
             */
            $seederBeforeLarave8 = Str::studly($tableNameWithoutPrefix) . 'TableSeeder';
            $pathBeforeLarave8   = database_path("seeds/{$seederBeforeLarave8}.php");

            $seederAfterLarave8 = Str::singular(Str::studly($tableNameWithoutPrefix)) . 'Seeder';
            $pathAfterLarave8   = database_path("seeders/{$seederAfterLarave8}.php");

            $isBeforeLarave8 = File::exists($pathBeforeLarave8);
            $isAfterLarave8  = File::exists($pathAfterLarave8);

            if ($isBeforeLarave8 || $isAfterLarave8) {
                DB::table($tableNameWithoutPrefix)->truncate();

                $seeder = $isBeforeLarave8 ? $seederBeforeLarave8 : $seederAfterLarave8;

                $this->artisan('db:seed', [
                    '--class' => $seeder,
                ]);
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
