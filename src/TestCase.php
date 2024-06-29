<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
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

    protected function createTestResponse($response, $request)
    {
        $testResponse = parent::createTestResponse($response, $request);

        if ($response instanceof JsonResponse) {
            return new class($testResponse) extends TestResponse {
                public function toSnapshot()
                {
                    return json_encode(
                        [
                            'status_code' => $this->getStatusCode(),
                            'headers'     => array_merge(
                                $this->headers->all(),
                                [
                                    'date'       => Carbon::now()->format('D, d M Y H:i:s T'),
                                    'set-cookie' => [
                                        'XSRF-TOKEN'      => '***',
                                        'laravel_session' => '***',
                                    ],
                                ]
                            ),
                            'content'     => $this->json(),
                        ],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                }
            };
        }

        return $testResponse;
    }

    public function getUri()
    {
        // e.g. P\Tests\Feature\Admin\Resources\ArticleTagControllerTest
        $classFQCN = static::class;

        // e.g. ArticleTagControllerTest
        $className = class_basename($classFQCN);

        // e.g. Admin\Resources\
        $prefixPath = Str::before(
            Str::after($classFQCN, 'P\\Tests\\Feature\\'), // e.g. Admin\Resources\ArticleTagControllerTest
            $className
        );

        // e.g. admin
        $prefix = Str::lower(
            Str::rtrim($prefixPath, '\\Resources\\')
        );

        if ($prefix !== '') {
            // e.g. admin/
            $prefix = Str::kebab($prefix) . '/';
        }

        // e.g. ArticleTags
        $resource = Str::plural(
            Str::before($className, 'ControllerTest') // e.g. ArticleTag
        );

        // e.g. api/admin/article-tags
        return 'api/' . $prefix . Str::kebab($resource);
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function snapshotIndex(array $parameter = [], $headers = []): void
    {
        $response = $this->getJson(
            $this->getUri() . '?' . http_build_query($parameter),
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotShow(string|int $id, array $parameter = [], $headers = []): void
    {
        $response = $this->getJson(
            $this->getUri() . '/' . $id . '?' . http_build_query($parameter),
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotStore(array $body, $headers = []): void
    {
        $response = $this->postJson(
            $this->getUri(),
            $body,
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotStoreValidation($headers = []): void
    {
        $response = $this->postJson(
            $this->getUri(),
            [],
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotUpdate(string|int $id, array $body, $headers = []): void
    {
        $response = $this->putJson(
            $this->getUri() . '/' . $id,
            $body,
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotUpdateValidation(string|int $id, $headers = []): void
    {
        $response = $this->postJson(
            $this->getUri() . '/' . $id,
            [],
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotDelete(string|int $id, $headers = []): void
    {
        $response = $this->deleteJson(
            $this->getUri() . '/' . $id,
            [],
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotRestore(string|int $id, $headers = []): void
    {
        $response = $this->patchJson(
            $this->getUri() . '/' . $id . '/restore',
            [],
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }

    public function snapshotForceDelete(string|int $id, $headers = []): void
    {
        $response = $this->deleteJson(
            $this->getUri() . '/' . $id . '/force-delete',
            [],
            array_merge($this->getHeaders(), $headers)
        );

        expect($response)->toMatchSnapshot();
    }
}
