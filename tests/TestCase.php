<?php

namespace Tests;

use App\Support\DatabaseResetGuard;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits()
    {
        $uses = $this->traitsUsedByTest ?? array_flip(class_uses_recursive(static::class));

        if (isset($uses[RefreshDatabase::class])
            || isset($uses[DatabaseMigrations::class])
            || isset($uses[DatabaseTruncation::class])) {
            $connection = (string) config('database.default');
            $database = config("database.connections.{$connection}.database");
            $database = $this->prepareIsolatedTestDatabase($connection, $database);

            app(DatabaseResetGuard::class)->assertCommandAllowed(
                'migrate:fresh',
                true,
                is_string($database) ? $database : null,
            );
        }

        return parent::setUpTraits();
    }

    protected function prepareIsolatedTestDatabase(string $connection, mixed $database): mixed
    {
        if ($connection !== 'sqlite' || $database !== ':memory:') {
            return $database;
        }

        $database = storage_path('framework/testing/numberwg_ci_test');
        $requiresMigration = ! is_file($database) || filesize($database) === 0;
        if (! is_dir(dirname($database)) || ! touch($database)) {
            throw new RuntimeException("Unable to prepare isolated SQLite test database [{$database}].");
        }

        config(["database.connections.{$connection}.database" => $database]);
        DB::purge($connection);
        if ($requiresMigration) {
            RefreshDatabaseState::$migrated = false;
        }

        return $database;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
