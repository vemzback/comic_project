<?php

namespace Tests\Feature;

use App\Models\Genre;
use Database\Seeders\CatalogSnapshotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSnapshotSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_contains_only_catalog_tables(): void
    {
        $snapshot = file_get_contents(database_path('catalog/initial_catalog.sql'));

        $this->assertIsString($snapshot);
        preg_match_all('/INSERT INTO `([^`]+)`/', $snapshot, $matches);

        $this->assertSame(
            ['chapters', 'comic_genres', 'comics', 'genres', 'pages'],
            collect($matches[1])->unique()->sort()->values()->all(),
        );
    }

    public function test_import_does_not_overwrite_an_existing_catalog(): void
    {
        Genre::factory()->create();

        app(CatalogSnapshotSeeder::class)->run();

        $this->assertDatabaseCount('genres', 1);
        $this->assertDatabaseCount('comics', 0);
        $this->assertDatabaseCount('chapters', 0);
        $this->assertDatabaseCount('pages', 0);
    }
}
