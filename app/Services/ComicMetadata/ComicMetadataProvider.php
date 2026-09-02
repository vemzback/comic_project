<?php

namespace App\Services\ComicMetadata;

interface ComicMetadataProvider
{
    public function key(): string;

    public function label(): string;

    /** @return array<int, array<string, mixed>> */
    public function search(string $query, int $limit = 10): array;

    /** @return array<string, mixed>|null */
    public function find(string $externalId): ?array;
}
