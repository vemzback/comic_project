<?php

namespace Tests\Feature;

use Database\Seeders\ComicPlatformSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Throwable;

class ProductionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $exception = $this->runSeederAndCaptureException(ComicPlatformSeeder::class);

        $this->assertNotNull($exception);
        $this->assertTrue(
            $exception instanceof \RuntimeException || $exception instanceof \LogicException,
        );
        $this->assertStringContainsString(
            'Demo seeding is disabled in production.',
            $exception->getMessage(),
        );
        $this->assertDatabaseMissing('users', ['email' => 'admin@comic.test']);
    }

    public function test_database_seeder_is_also_blocked_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $exception = $this->runSeederAndCaptureException(DatabaseSeeder::class);

        $this->assertNotNull($exception);
        $this->assertTrue(
            $exception instanceof \RuntimeException || $exception instanceof \LogicException,
        );
        $this->assertStringContainsString(
            'Demo seeding is disabled in production.',
            $exception->getMessage(),
        );
        $this->assertDatabaseMissing('users', ['email' => 'admin@comic.test']);
    }

    public function test_demo_seeder_remains_available_outside_production(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');

        $exception = $this->runSeederAndCaptureException(ComicPlatformSeeder::class);

        $this->assertNull($exception);
        $this->assertDatabaseHas('users', ['email' => 'admin@comic.test']);
    }

    /**
     * @param  class-string  $seederClass
     */
    private function runSeederAndCaptureException(string $seederClass): ?Throwable
    {
        try {
            app($seederClass)->run();
        } catch (Throwable $exception) {
            return $exception;
        }

        return null;
    }
}
