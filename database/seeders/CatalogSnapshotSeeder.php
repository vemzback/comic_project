<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CatalogSnapshotSeeder extends Seeder
{
    /**
     * Import the curated catalog without copying user-owned activity.
     */
    public function run(): void
    {
        $catalogTables = ['genres', 'comics', 'comic_genres', 'chapters', 'pages'];

        foreach ($catalogTables as $table) {
            if (DB::table($table)->exists()) {
                $this->command?->warn(
                    "Initial catalog import skipped because the [{$table}] table already contains data."
                );

                return;
            }
        }

        $snapshotPath = database_path('catalog/initial_catalog.sql');

        if (! is_file($snapshotPath) || ! is_readable($snapshotPath)) {
            throw new RuntimeException('The initial catalog snapshot is missing or unreadable.');
        }

        DB::transaction(function () use ($snapshotPath): void {
            $snapshot = fopen($snapshotPath, 'rb');

            if ($snapshot === false) {
                throw new RuntimeException('The initial catalog snapshot could not be opened.');
            }

            try {
                while (($statement = fgets($snapshot)) !== false) {
                    $statement = trim($statement);

                    if ($statement !== '') {
                        DB::unprepared($statement);
                    }
                }
            } finally {
                fclose($snapshot);
            }
        });

        $this->command?->info('Initial catalog imported without users or community activity.');
    }
}
