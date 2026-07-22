<?php

namespace App\Support;

use RuntimeException;

class DatabaseResetGuard
{
    private const FORBIDDEN_COMMANDS = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
    ];

    public function assertDirectInvocationAllowed(array $arguments): void
    {
        if ($this->isHelpRequest($arguments)) {
            return;
        }

        foreach (self::FORBIDDEN_COMMANDS as $command) {
            if (in_array($command, $arguments, true)) {
                throw $this->blocked($command);
            }
        }
    }

    public function assertCommandAllowed(
        string $command,
        bool $runningUnitTests,
        ?string $databaseName
    ): void {
        if (! in_array($command, self::FORBIDDEN_COMMANDS, true)) {
            return;
        }

        if ($runningUnitTests && $this->isIsolatedTestDatabase($databaseName)) {
            return;
        }

        throw $this->blocked($command);
    }

    private function isHelpRequest(array $arguments): bool
    {
        return in_array('help', $arguments, true)
            || in_array('--help', $arguments, true)
            || in_array('-h', $arguments, true);
    }

    private function isIsolatedTestDatabase(?string $databaseName): bool
    {
        return is_string($databaseName)
            && $databaseName !== ''
            && str_ends_with($databaseName, '_test');
    }

    private function blocked(string $command): RuntimeException
    {
        return new RuntimeException(
            "Database reset command [{$command}] is forbidden. Use PHPUnit RefreshDatabase only with an isolated *_test database."
        );
    }
}
