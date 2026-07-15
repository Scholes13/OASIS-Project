<?php

namespace Tests\Unit\Support;

use App\Support\DatabaseResetGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseResetGuardTest extends TestCase
{
    #[DataProvider('forbiddenCommands')]
    public function test_it_blocks_direct_database_reset_commands(string $command): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Database reset command [{$command}] is forbidden.");

        (new DatabaseResetGuard)->assertDirectInvocationAllowed(['artisan', $command]);
    }

    public function test_it_allows_non_destructive_and_help_commands(): void
    {
        $guard = new DatabaseResetGuard;

        $guard->assertDirectInvocationAllowed(['artisan', 'migrate']);
        $guard->assertDirectInvocationAllowed(['artisan', 'help', 'migrate:fresh']);

        $this->addToAssertionCount(2);
    }

    public function test_it_allows_framework_refresh_only_during_tests_on_an_isolated_database(): void
    {
        (new DatabaseResetGuard)->assertCommandAllowed('migrate:fresh', true, 'numberwg_test');

        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeTestContexts')]
    public function test_it_blocks_framework_refresh_outside_an_isolated_test_database(
        bool $runningUnitTests,
        ?string $databaseName
    ): void {
        $this->expectException(RuntimeException::class);

        (new DatabaseResetGuard)->assertCommandAllowed(
            'migrate:fresh',
            $runningUnitTests,
            $databaseName,
        );
    }

    public static function forbiddenCommands(): array
    {
        return [
            ['migrate:fresh'],
            ['migrate:refresh'],
            ['migrate:reset'],
            ['db:wipe'],
        ];
    }

    public static function unsafeTestContexts(): array
    {
        return [
            'local application database' => [false, 'numberwg'],
            'testing flag with application database' => [true, 'numberwg'],
            'test-like name without suffix' => [true, 'numberwg_testing'],
            'missing database name' => [true, null],
        ];
    }
}
