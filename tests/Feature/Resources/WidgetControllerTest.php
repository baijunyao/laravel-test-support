<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Route::get('api/widgets', fn () => response()->json(['items' => ['示例']]));
    Route::get('api/widgets/{id}', fn ($id) => response()->json(['id' => $id]));
    Route::post('api/widgets', function () {
        request()->validate(['name' => 'required']);

        return response()->json(['name' => request('name')], 201);
    });
    Route::put('api/widgets/{id}', fn ($id) => response()->json(['id' => $id, 'name' => request('name')]));
    Route::post('api/widgets/{id}', function () {
        request()->validate(['name' => 'required']);
    });
    Route::delete('api/widgets/{id}', fn () => response()->noContent());
    Route::patch('api/widgets/{id}/restore', fn () => response()->json(['restored' => true]));
    Route::delete('api/widgets/{id}/force-delete', fn () => response()->noContent());
    Route::get('plain-response', fn () => response('hello'));
});

foreach ([
    'index' => ['snapshotIndex', []],
    'show' => ['snapshotShow', [1]],
    'store' => ['snapshotStore', [['name' => '示例']]],
    'store validation' => ['snapshotStoreValidation', []],
    'update' => ['snapshotUpdate', [1, ['name' => '更新']]],
    'update validation' => ['snapshotUpdateValidation', [1]],
    'delete' => ['snapshotDelete', [1]],
    'restore' => ['snapshotRestore', [1]],
    'force delete' => ['snapshotForceDelete', [1]],
] as $scenario => [$method, $arguments]) {
    it("matches {$scenario} response snapshot", function () use ($method, $arguments): void {
        expect($this->getUri())->toBe('api/widgets');
        $this->{$method}(...$arguments);
    });
}

it('cleans written tables before destroying the application', function (): void {
    Schema::create('compatibility_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
    DB::table('compatibility_records')->insert(['name' => 'Laravel']);
    expect(DB::table('compatibility_records')->count())->toBe(1);

    $this->beforeApplicationDestroyed(function (): void {
        expect(DB::table('compatibility_records')->count())->toBe(0);
        expect((int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys)->toBe(1);
    });
});

it('normalizes snapshot dates and cookies', function (): void {
    $snapshot = json_decode($this->getJson('api/widgets')->toSnapshot(), true, flags: JSON_THROW_ON_ERROR);

    expect(Carbon::now()->year)->toBe(2019)
        ->and($snapshot['headers']['date'])->toBe('Tue, 01 Jan 2019 00:00:00 UTC')
        ->and($snapshot['headers']['set-cookie'])->toBe([
            'XSRF-TOKEN' => '***',
            'laravel_session' => '***',
        ])
        ->and($snapshot['content'])->toBe(['items' => ['示例']]);
});

it('preserves plain responses', function (): void {
    $this->get('plain-response')->assertOk()->assertSee('hello');
});
