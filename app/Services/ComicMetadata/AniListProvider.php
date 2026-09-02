<?php

namespace App\Services\ComicMetadata;

use Illuminate\Support\Facades\Http;

class AniListProvider implements ComicMetadataProvider
{
    public function key(): string
    {
        return 'anilist';
    }

    public function label(): string
    {
        return 'AniList (Manga)';
    }

    public function search(string $query, int $limit = 10): array
    {
        $graphql = <<<'GRAPHQL'
query ($search: String!, $perPage: Int!) {
  Page(page: 1, perPage: $perPage) {
    media(search: $search, type: MANGA, sort: SEARCH_MATCH, isAdult: false) {
      id
      title { romaji english native }
      description(asHtml: false)
      status
      startDate { year month day }
      genres
      coverImage { extraLarge large }
      siteUrl
      staff(perPage: 8, sort: RELEVANCE) {
        edges { role node { name { full } } }
      }
    }
  }
}
GRAPHQL;

        $data = $this->request($graphql, [
            'search' => $query,
            'perPage' => min(max($limit, 1), 20),
        ]);

        return collect(data_get($data, 'data.Page.media', []))
            ->map(fn (array $media) => $this->normalize($media))
            ->all();
    }

    public function find(string $externalId): ?array
    {
        if (! ctype_digit($externalId)) {
            return null;
        }

        $graphql = <<<'GRAPHQL'
query ($id: Int!) {
  Media(id: $id, type: MANGA) {
    id
    title { romaji english native }
    description(asHtml: false)
    status
    startDate { year month day }
    genres
    coverImage { extraLarge large }
    siteUrl
    staff(perPage: 8, sort: RELEVANCE) {
      edges { role node { name { full } } }
    }
  }
}
GRAPHQL;

        $media = data_get($this->request($graphql, ['id' => (int) $externalId]), 'data.Media');

        return is_array($media) ? $this->normalize($media) : null;
    }

    /** @return array<string, mixed> */
    private function request(string $query, array $variables): array
    {
        return Http::acceptJson()
            ->withUserAgent(config('app.name').'/1.0')
            ->timeout(10)
            ->retry(2, 250)
            ->post(config('services.comic_metadata.anilist_endpoint'), compact('query', 'variables'))
            ->throw()
            ->json();
    }

    /** @return array<string, mixed> */
    private function normalize(array $media): array
    {
        $staff = collect(data_get($media, 'staff.edges', []));
        $preferredStaff = $staff->filter(fn (array $edge) => str_contains(strtolower((string) ($edge['role'] ?? '')), 'story'));
        $authors = ($preferredStaff->isNotEmpty() ? $preferredStaff : $staff)
            ->pluck('node.name.full')
            ->filter()
            ->unique()
            ->take(4)
            ->implode(', ');

        return [
            'provider' => $this->key(),
            'external_id' => (string) $media['id'],
            'title' => data_get($media, 'title.english') ?: data_get($media, 'title.romaji') ?: data_get($media, 'title.native'),
            'description' => $this->cleanText($media['description'] ?? null),
            'author' => $authors ?: null,
            'publisher' => null,
            'original_published_at' => $this->dateFromParts($media['startDate'] ?? []),
            'status' => match ($media['status'] ?? null) {
                'FINISHED' => 'completed',
                'HIATUS', 'CANCELLED' => 'hiatus',
                default => 'ongoing',
            },
            'genres' => array_values(array_filter($media['genres'] ?? [])),
            'cover_url' => $this->safeHttpsUrl(data_get($media, 'coverImage.extraLarge') ?: data_get($media, 'coverImage.large')),
            'source_url' => $this->safeHttpsUrl($media['siteUrl'] ?? null),
        ];
    }

    private function cleanText(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", (string) $text);

        return trim((string) $text) ?: null;
    }

    private function dateFromParts(array $date): ?string
    {
        if (empty($date['year'])) {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            $date['year'],
            ($date['month'] ?? 1) ?: 1,
            ($date['day'] ?? 1) ?: 1
        );
    }

    private function safeHttpsUrl(?string $url): ?string
    {
        return $url && parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : null;
    }
}
