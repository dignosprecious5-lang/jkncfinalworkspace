<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
beforeEach(function (): void {
    config([
        'database.default' => 'assignment_migration_test',
        'database.connections.assignment_migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('assignment_migration_test');

    Schema::connection('assignment_migration_test')->create('users', function (Blueprint $table): void {
        $table->id();
    });
    Schema::connection('assignment_migration_test')->create('transmittals', function (Blueprint $table): void {
        $table->id();
    });
    Schema::connection('assignment_migration_test')->create('user_permissions', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
    });
    Schema::connection('assignment_migration_test')->create('role_permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('role');
    });
});

afterEach(function (): void {
    DB::purge('assignment_migration_test');
});

test('assignment migration adds nullable references and deny-by-default permission columns on isolated SQLite', function (): void {
    $userId = DB::table('users')->insertGetId([]);
    $migration = require database_path('migrations/2026_11_23_000001_add_transmittal_assignment_security.php');

    $migration->up();

    foreach (['assigned_reviewer_id', 'v1_preparer_user_id', 'v1_assigned_reviewer_id'] as $column) {
        expect(Schema::hasColumn('transmittals', $column))->toBeTrue();
    }
    expect(Schema::hasColumn('user_permissions', 'transmittal_v2_review'))->toBeTrue();
    expect(Schema::hasColumn('role_permissions', 'transmittal_v2_review'))->toBeTrue();

    $permissionId = DB::table('user_permissions')->insertGetId(['user_id' => $userId]);
    $rolePermissionId = DB::table('role_permissions')->insertGetId(['role' => 'Employee']);
    expect((int) DB::table('user_permissions')->where('id', $permissionId)->value('transmittal_v2_review'))->toBe(0);
    expect((int) DB::table('role_permissions')->where('id', $rolePermissionId)->value('transmittal_v2_review'))->toBe(0);

    $transmittalForeignKeys = DB::select('PRAGMA foreign_key_list(transmittals)');
    foreach (['assigned_reviewer_id', 'v1_preparer_user_id', 'v1_assigned_reviewer_id'] as $column) {
        $foreignKey = collect($transmittalForeignKeys)->firstWhere('from', $column);
        expect($foreignKey)->not->toBeNull();
        expect(strtolower($foreignKey->table))->toBe('users');
        expect(strtolower($foreignKey->on_delete))->toBe('set null');
    }

    expect(Schema::hasTable('transmittal_v1_assignment_histories'))->toBeTrue();
    $historyForeignKeys = DB::select('PRAGMA foreign_key_list(transmittal_v1_assignment_histories)');
    expect(collect($historyForeignKeys)->pluck('table')->map(fn ($table) => strtolower($table))->all())
        ->toContain('users')->toContain('transmittals');

    DB::table('transmittals')->insert(['v1_preparer_user_id' => $userId]);
    $migration->down();
    expect(Schema::hasTable('transmittal_v1_assignment_histories'))->toBeTrue();
    expect(DB::table('transmittals')->value('v1_preparer_user_id'))->toBe($userId);
});
