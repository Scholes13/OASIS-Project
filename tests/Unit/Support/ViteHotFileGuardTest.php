<?php

namespace Tests\Unit\Support;

use App\Support\ViteHotFileGuard;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ViteHotFileGuardTest extends TestCase
{
    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'vite-hot-guard-'.uniqid('', true);

        mkdir($this->tempDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->tempDirectory);

        parent::tearDown();
    }

    public function test_it_deletes_the_hot_file_outside_local_and_testing(): void
    {
        $hotFile = $this->tempDirectory.DIRECTORY_SEPARATOR.'hot';
        file_put_contents($hotFile, 'http://127.0.0.1:5173');

        $guard = new ViteHotFileGuard(new Filesystem);

        $deleted = $guard->cleanup('production', $hotFile);

        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($hotFile);
    }

    public function test_it_keeps_the_hot_file_for_local_environment(): void
    {
        $hotFile = $this->tempDirectory.DIRECTORY_SEPARATOR.'hot';
        file_put_contents($hotFile, 'http://127.0.0.1:5173');

        $guard = new ViteHotFileGuard(new Filesystem);

        $deleted = $guard->cleanup('local', $hotFile);

        $this->assertFalse($deleted);
        $this->assertFileExists($hotFile);
    }

    public function test_it_ignores_missing_hot_files(): void
    {
        $hotFile = $this->tempDirectory.DIRECTORY_SEPARATOR.'missing-hot';

        $guard = new ViteHotFileGuard(new Filesystem);

        $deleted = $guard->cleanup('production', $hotFile);

        $this->assertFalse($deleted);
        $this->assertFileDoesNotExist($hotFile);
    }
}
