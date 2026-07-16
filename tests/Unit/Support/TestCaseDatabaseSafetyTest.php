<?php

namespace Tests\Unit\Support;

use Tests\TestCase;

class TestCaseDatabaseSafetyTest extends TestCase
{
    public function test_it_normalizes_legacy_in_memory_sqlite_to_an_isolated_test_file(): void
    {
        $database = storage_path('framework/testing/numberwg_ci_test');
        @unlink($database);
        config(['database.connections.sqlite.database' => ':memory:']);

        try {
            $resolvedDatabase = $this->prepareIsolatedTestDatabase('sqlite', ':memory:');

            $this->assertSame($database, $resolvedDatabase);
            $this->assertSame($database, config('database.connections.sqlite.database'));
            $this->assertStringEndsWith('_test', $resolvedDatabase);
            $this->assertFileExists($resolvedDatabase);
        } finally {
            @unlink($database);
        }
    }

    public function test_it_preserves_explicit_test_database_configuration(): void
    {
        $database = storage_path('framework/testing/explicit_test');

        $this->assertSame(
            $database,
            $this->prepareIsolatedTestDatabase('sqlite', $database),
        );
    }
}
