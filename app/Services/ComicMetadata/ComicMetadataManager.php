<?php

namespace App\Services\ComicMetadata;

use InvalidArgumentException;

class ComicMetadataManager
{
    /** @var array<string, ComicMetadataProvider> */
    private array $providers;

    public function __construct(AniListProvider $aniList, GoogleBooksProvider $googleBooks)
    {
        $this->providers = [
            $aniList->key() => $aniList,
            $googleBooks->key() => $googleBooks,
        ];
    }

    /** @return array<string, string> */
    public function options(): array
    {
        return array_map(fn (ComicMetadataProvider $provider) => $provider->label(), $this->providers);
    }

    public function provider(string $key): ComicMetadataProvider
    {
        return $this->providers[$key]
            ?? throw new InvalidArgumentException('Unsupported comic metadata provider.');
    }
}
