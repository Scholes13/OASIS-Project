<?php

namespace Tests;

use App\Support\DatabaseResetGuard;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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

            app(DatabaseResetGuard::class)->assertCommandAllowed(
                'migrate:fresh',
                true,
                is_string($database) ? $database : null,
            );
        }

        return parent::setUpTraits();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
