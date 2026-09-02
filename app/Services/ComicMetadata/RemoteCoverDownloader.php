<?php

namespace App\Services\ComicMetadata;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RemoteCoverDownloader
{
    public function download(array $metadata): ?string
    {
        $url = $metadata['cover_url'] ?? null;
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw ValidationException::withMessages(['import' => 'The provider returned an insecure cover URL.']);
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! $this->hostIsAllowed((string) ($metadata['provider'] ?? ''), $host)) {
            throw ValidationException::withMessages(['import' => 'The provider returned an unsupported cover host.']);
        }

        $response = Http::timeout(12)->retry(2, 250)->get($url)->throw();
        $contentType = strtolower(trim(explode(';', $response->header('Content-Type', ''))[0]));
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (! isset($extensions[$contentType]) || strlen($response->body()) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['import' => 'The provider cover is not a supported image or is larger than 5 MB.']);
        }

        $provider = preg_replace('/[^a-z0-9_-]/i', '', (string) $metadata['provider']);
        $externalId = preg_replace('/[^a-z0-9_-]/i', '', (string) $metadata['external_id']);
        $path = sprintf('comics/covers/import_%s_%s_%s.%s', $provider, $externalId, bin2hex(random_bytes(4)), $extensions[$contentType]);

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    private function hostIsAllowed(string $provider, string $host): bool
    {
        return match ($provider) {
            'anilist' => $host === 's4.anilist.co',
            'google_books' => $host === 'books.google.com'
                || $host === 'books.googleusercontent.com'
                || str_ends_with($host, '.googleusercontent.com'),
            default => false,
        };
    }
}
