<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Page;
use App\Services\ComicPublicationReadiness;
use App\Services\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComicController extends Controller
{
    public function index(ComicPublicationReadiness $readiness): View
    {
        $comics = Comic::query()
            ->with(['genres', 'chapters.pages'])
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $publicationReadiness = $comics->getCollection()
            ->mapWithKeys(fn (Comic $comic): array => [$comic->id => $readiness->evaluate($comic)]);

        return view('admin.comics.index', compact('comics', 'publicationReadiness'));
    }

    public function create(): View
    {
        $genres = Genre::query()->orderBy('name')->get();

        return view('admin.comics.create', compact('genres'));
    }

    public function store(Request $request, PublicImageStorage $images): RedirectResponse
    {
        $validated = $this->validateComic($request, null);

        if (! empty($validated['published_at'])) {
            throw ValidationException::withMessages([
                'published_at' => ['Create the comic as a draft first. Add a complete chapter, then publish it from the Edit page.'],
            ]);
        }

        $coverPath = $images->store(
            $request->file('cover_image'),
            'comics/covers',
            'cover_image',
            $request->input('cover_image'),
        );

        try {
            $comic = DB::transaction(function () use ($validated, $coverPath) {
                $comic = Comic::create([
                    'title' => $validated['title'],
                    'slug' => $validated['slug'],
                    'description' => $validated['description'] ?? null,
                    'author' => $validated['author'] ?? null,
                    'publisher' => $validated['publisher'] ?? null,
                    'original_published_at' => $validated['original_published_at'] ?? null,
                    'cover_image' => $coverPath,
                    'status' => $validated['status'],
                    'published_at' => null,
                    'is_featured' => (bool) ($validated['is_featured'] ?? false),
                    'seo_title' => $validated['seo_title'] ?? null,
                    'seo_description' => $validated['seo_description'] ?? null,
                ]);

                $comic->genres()->sync($validated['genres'] ?? []);

                return $comic;
            });
        } catch (\Throwable $exception) {
            if ($request->hasFile('cover_image') && $coverPath) {
                $images->delete($coverPath);
            }

            throw $exception;
        }

        return redirect()->route('admin.comics.index')->with('success', 'Comic created successfully.');
    }

    public function show(Comic $comic, ComicPublicationReadiness $readiness): View
    {
        $comic->load(['genres', 'chapters.pages']);
        $publicationReadiness = $readiness->evaluate($comic);

        return view('admin.comics.show', compact('comic', 'publicationReadiness'));
    }

    public function edit(Comic $comic, ComicPublicationReadiness $readiness): View
    {
        $comic->load(['genres', 'chapters.pages']);
        $genres = Genre::query()->orderBy('name')->get();
        $publicationReadiness = $readiness->evaluate($comic);

        return view('admin.comics.edit', compact('comic', 'genres', 'publicationReadiness'));
    }

    public function update(
        Request $request,
        Comic $comic,
        ComicPublicationReadiness $readiness,
        PublicImageStorage $images,
    ): RedirectResponse {
        $validated = $this->validateComic($request, $comic);
        $oldCoverPath = $comic->cover_image;
        $newCoverPath = $request->hasFile('cover_image')
            ? $images->store($request->file('cover_image'), 'comics/covers', 'cover_image')
            : null;
        $requestedCoverPath = is_string($request->input('cover_image')) && $request->input('cover_image') !== ''
            ? $request->input('cover_image')
            : null;

        try {
            DB::transaction(function () use ($comic, $validated, $newCoverPath, $requestedCoverPath, $readiness) {
                $comic->update([
                    'title' => $validated['title'],
                    'slug' => $validated['slug'],
                    'description' => $validated['description'] ?? null,
                    'author' => $validated['author'] ?? null,
                    'publisher' => $validated['publisher'] ?? null,
                    'original_published_at' => $validated['original_published_at'] ?? null,
                    'cover_image' => $newCoverPath ?? $requestedCoverPath ?? $comic->cover_image,
                    'status' => $validated['status'],
                    'published_at' => $validated['published_at'] ?? null,
                    'is_featured' => (bool) ($validated['is_featured'] ?? false),
                    'seo_title' => $validated['seo_title'] ?? null,
                    'seo_description' => $validated['seo_description'] ?? null,
                ]);

                $comic->genres()->sync($validated['genres'] ?? []);

                if ($comic->published_at) {
                    $comic->load(['genres', 'chapters.pages']);
                    $result = $readiness->evaluate($comic);

                    if (! $result['ready']) {
                        throw ValidationException::withMessages([
                            'published_at' => ['This comic is not ready to publish: '.implode(' ', $result['blockers'])],
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            if ($newCoverPath) {
                $images->delete($newCoverPath);
            }

            throw $exception;
        }

        if ($newCoverPath && $oldCoverPath && $oldCoverPath !== $newCoverPath) {
            $images->delete($oldCoverPath);
        }

        return redirect()->route('admin.comics.index')->with('success', 'Comic updated successfully.');
    }

    public function destroy(Comic $comic, PublicImageStorage $images): RedirectResponse
    {
        if ($comic->published_at) {
            throw ValidationException::withMessages([
                'comic' => ['Unpublish this comic before deleting it. This protects content that is currently public or scheduled.'],
            ]);
        }

        $pagePaths = Page::query()
            ->whereHas('chapter', fn ($query) => $query->where('comic_id', $comic->id))
            ->pluck('image_path')
            ->filter()
            ->unique();

        foreach ($pagePaths as $pagePath) {
            $images->delete($pagePath);
        }

        if (! empty($comic->cover_image)) {
            $images->delete($comic->cover_image);
        }

        $comic->delete();

        return redirect()->route('admin.comics.index')->with('success', 'Comic deleted successfully.');
    }

    protected function validateComic(Request $request, ?Comic $comic): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:comics,slug'.($comic ? ','.$comic->id : '')],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'original_published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable'],
            'status' => ['required', 'string', 'in:ongoing,completed,hiatus'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('cover_image')) {
            $request->validate([
                'cover_image' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            ]);
        }

        return $validated;
    }
}
