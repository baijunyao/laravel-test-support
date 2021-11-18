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
            preg_match_all('/^(?:insert|update|delete).+?`(.+?)`.*/', $queryLog['query'], $table);

            if (isset($table[1][0]) && !in_array($table[1][0], $dirtyTables, true)) {
                $dirtyTables[] = $table[1][0];
            }
        }

        foreach (array_unique($dirtyTables) as $dirtyTable) {
            $tableNameWithoutPrefix = str_replace(config('database.connections.mysql.prefix'), '', $dirtyTable);
            $seeder                 = Str::studly($tableNameWithoutPrefix) . 'TableSeeder';

            if (File::exists(database_path("seeds/{$seeder}.php"))) {
                DB::table($tableNameWithoutPrefix)->truncate();

                $this->artisan('db:seed', [
                    '--class' => $seeder,
                ]);
            }
        }

        parent::tearDown();
    }

    public function assertResponse(TestResponse $response, $headersIgnores = null, $contentIgnores = null)
    {
        $content = $response->getContent();

        $this->assertMatchesJsonSnapshot([
            'status_code' => $response->getStatusCode(),
            'headers' => array_merge($response->headers->all(), ['date' => Carbon::now()->format('D, d M Y H:i:s T')]),
            'content' => $content === '' ? '' : json_decode($content, true),
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
}
