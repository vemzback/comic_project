<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Genre;
use App\Services\ComicMetadata\ComicMetadataManager;
use App\Services\ComicMetadata\RemoteCoverDownloader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ComicImportController extends Controller
{
    public function index(Request $request, ComicMetadataManager $manager): View
    {
        $providers = $manager->options();
        $query = trim((string) $request->query('q', ''));
        $providerKey = (string) $request->query('provider', 'anilist');
        $results = [];
        $apiError = null;
        $importedIds = [];

        if ($query !== '') {
            $validated = $request->validate([
                'q' => ['required', 'string', 'min:2', 'max:120'],
                'provider' => ['required', Rule::in(array_keys($providers))],
            ]);

            try {
                $results = $manager->provider($validated['provider'])->search($validated['q'], 12);
                $importedIds = Comic::query()
                    ->where('external_provider', $validated['provider'])
                    ->whereIn('external_id', collect($results)->pluck('external_id'))
                    ->pluck('external_id')
                    ->all();
            } catch (Throwable $exception) {
                report($exception);
                $apiError = 'The metadata provider could not be reached. Please try again shortly.';
            }
        }

        return view('admin.comics.import', compact('providers', 'providerKey', 'query', 'results', 'apiError', 'importedIds'));
    }

    public function store(
        Request $request,
        ComicMetadataManager $manager,
        RemoteCoverDownloader $coverDownloader
    ): RedirectResponse {
        $providers = $manager->options();
        $validated = $request->validate([
            'provider' => ['required', Rule::in(array_keys($providers))],
            'external_id' => ['required', 'string', 'max:120'],
        ]);

        if (Comic::query()
            ->where('external_provider', $validated['provider'])
            ->where('external_id', $validated['external_id'])
            ->exists()) {
            throw ValidationException::withMessages(['import' => 'This provider entry has already been imported.']);
        }

        try {
            $metadata = $manager->provider($validated['provider'])->find($validated['external_id']);
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['import' => 'The metadata provider could not be reached. Please try again.']);
        }

        if (! $metadata || blank($metadata['title'] ?? null)) {
            throw ValidationException::withMessages(['import' => 'The selected provider entry is no longer available.']);
        }

        try {
            $coverPath = $coverDownloader->download($metadata);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['import' => 'The cover could not be downloaded. Try importing again later.']);
        }

        try {
            $comic = DB::transaction(function () use ($metadata, $coverPath): Comic {
                $comic = Comic::create([
                    'external_provider' => $metadata['provider'],
                    'external_id' => $metadata['external_id'],
                    'source_url' => $metadata['source_url'] ?? null,
                    'title' => $metadata['title'],
                    'slug' => $this->uniqueSlug($metadata['title']),
                    'description' => $metadata['description'] ?? null,
                    'author' => $metadata['author'] ?? null,
                    'publisher' => $metadata['publisher'] ?? null,
                    'original_published_at' => $metadata['original_published_at'] ?? null,
                    'cover_image' => $coverPath,
                    'status' => $metadata['status'] ?? 'ongoing',
                    'published_at' => null,
                    'is_featured' => false,
                    'seo_title' => Str::limit($metadata['title'], 255, ''),
                    'seo_description' => Str::limit((string) ($metadata['description'] ?? ''), 500, ''),
                    'source_metadata' => [
                        'genres' => $metadata['genres'] ?? [],
                        'cover_url' => $metadata['cover_url'] ?? null,
                        'preview' => $metadata['preview'] ?? null,
                        'imported_at' => now()->toIso8601String(),
                    ],
                ]);

                $genreLookup = Genre::query()->get()->keyBy(fn (Genre $genre) => mb_strtolower($genre->name));
                $genreIds = collect($metadata['genres'] ?? [])
                    ->map(fn (string $genre) => $genreLookup->get(mb_strtolower($genre))?->id)
                    ->filter()
                    ->unique()
                    ->values();

                $comic->genres()->sync($genreIds);

                return $comic;
            });
        } catch (Throwable $exception) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }

            throw $exception;
        }

        return redirect()->route('admin.comics.show', $comic)
            ->with('success', 'Comic metadata imported as a draft. Review it before publishing.');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'imported-comic';
        $slug = $base;
        $suffix = 2;

        while (Comic::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
