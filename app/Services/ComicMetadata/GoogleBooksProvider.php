<?php

namespace App\Services\ComicMetadata;

use Illuminate\Support\Facades\Http;

class GoogleBooksProvider implements ComicMetadataProvider
{
    public function key(): string
    {
        return 'google_books';
    }

    public function label(): string
    {
        return 'Google Books (API key recommended)';
    }

    public function search(string $query, int $limit = 10): array
    {
        $params = [
            'q' => $query,
            'printType' => 'books',
            'projection' => 'lite',
            'maxResults' => min(max($limit, 1), 20),
        ];

        if ($key = config('services.comic_metadata.google_books_key')) {
            $params['key'] = $key;
        }

        $data = Http::acceptJson()
            ->withUserAgent(config('app.name').'/1.0')
            ->timeout(10)
            ->retry(2, 250)
            ->get(config('services.comic_metadata.google_books_endpoint'), $params)
            ->throw()
            ->json();

        return collect($data['items'] ?? [])
            ->map(fn (array $volume) => $this->normalize($volume))
            ->filter(fn (array $volume) => filled($volume['title']))
            ->values()
            ->all();
    }

    public function find(string $externalId): ?array
    {
        $params = [];
        if ($key = config('services.comic_metadata.google_books_key')) {
            $params['key'] = $key;
        }

        $response = Http::acceptJson()
            ->withUserAgent(config('app.name').'/1.0')
            ->timeout(10)
            ->retry(2, 250)
            ->get(rtrim(config('services.comic_metadata.google_books_endpoint'), '/').'/'.rawurlencode($externalId), $params);

        if ($response->notFound()) {
            return null;
        }

        return $this->normalize($response->throw()->json());
    }

    /** @return array<string, mixed> */
    private function normalize(array $volume): array
    {
        $info = $volume['volumeInfo'] ?? [];
        $access = $volume['accessInfo'] ?? [];
        $cover = data_get($info, 'imageLinks.extraLarge')
            ?: data_get($info, 'imageLinks.large')
            ?: data_get($info, 'imageLinks.medium')
            ?: data_get($info, 'imageLinks.thumbnail');

        return [
            'provider' => $this->key(),
            'external_id' => (string) ($volume['id'] ?? ''),
            'title' => $info['title'] ?? null,
            'description' => $this->cleanText($info['description'] ?? null),
            'author' => collect($info['authors'] ?? [])->filter()->take(4)->implode(', ') ?: null,
            'publisher' => $info['publisher'] ?? null,
            'original_published_at' => $this->normalizeDate($info['publishedDate'] ?? null),
            'status' => 'completed',
            'genres' => array_values(array_filter($info['categories'] ?? [])),
            'cover_url' => $this->safeHttpsUrl($cover),
            'source_url' => $this->safeHttpsUrl($info['canonicalVolumeLink'] ?? $info['infoLink'] ?? null),
            'preview' => [
                'embeddable' => (bool) ($access['embeddable'] ?? false),
                'viewability' => $this->cleanIdentifier($access['viewability'] ?? null),
                'access_view_status' => $this->cleanIdentifier($access['accessViewStatus'] ?? null),
                'public_domain' => (bool) ($access['publicDomain'] ?? false),
                'web_reader_url' => $this->safeHttpsUrl($access['webReaderLink'] ?? null),
                'epub_available' => (bool) data_get($access, 'epub.isAvailable', false),
                'pdf_available' => (bool) data_get($access, 'pdf.isAvailable', false),
            ],
        ];
    }

    private function cleanIdentifier(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return preg_match('/^[A-Z0-9_]+$/i', $value) ? $value : null;
    }

    private function cleanText(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: null;
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! $date || ! preg_match('/^(\d{4})(?:-(\d{1,2}))?(?:-(\d{1,2}))?/', $date, $parts)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $parts[1], (int) ($parts[2] ?? 1), (int) ($parts[3] ?? 1));
    }

    private function safeHttpsUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = preg_replace('/^http:/i', 'https:', $url);

        return parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : null;
    }
}
