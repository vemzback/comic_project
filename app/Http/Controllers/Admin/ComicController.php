<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Genre;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ComicController extends Controller
{
    public function index(): View
    {
        $comics = Comic::query()
            ->with('genres')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('admin.comics.index', compact('comics'));
    }

    public function create(): View
    {
        $genres = Genre::query()->orderBy('name')->get();

        return view('admin.comics.create', compact('genres'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateComic($request, null);

        $comic = DB::transaction(function () use ($validated, $request) {
            $coverPath = $this->storeMediaFile($request->file('cover_image'), 'comics/covers', $request->input('cover_image'));

            $comic = Comic::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'cover_image' => $coverPath ?? ($request->hasFile('cover_image') ? null : $request->input('cover_image')) ?? null,
                'status' => $validated['status'],
                'published_at' => $validated['published_at'] ?? null,
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
            ]);

            $comic->genres()->sync($validated['genres'] ?? []);

            return $comic;
        });

        return redirect()->route('admin.comics.index')->with('success', 'Comic created successfully.');
    }

    public function show(Comic $comic): View
    {
        $comic->load(['genres', 'chapters']);

        return view('admin.comics.show', compact('comic'));
    }

    public function edit(Comic $comic): View
    {
        $comic->load('genres');
        $genres = Genre::query()->orderBy('name')->get();

        return view('admin.comics.edit', compact('comic', 'genres'));
    }

    public function update(Request $request, Comic $comic): RedirectResponse
    {
        $validated = $this->validateComic($request, $comic);

        DB::transaction(function () use ($comic, $request, $validated) {
            $coverPath = $this->handleUploadedMedia($comic->cover_image, $request->file('cover_image'), 'comics/covers', $request->input('cover_image'));

            $comic->update([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'cover_image' => $coverPath ?? $comic->cover_image,
                'status' => $validated['status'],
                'published_at' => $validated['published_at'] ?? null,
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
            ]);

            $comic->genres()->sync($validated['genres'] ?? []);
        });

        return redirect()->route('admin.comics.index')->with('success', 'Comic updated successfully.');
    }

    public function destroy(Comic $comic): RedirectResponse
    {
        $publicDisk = Storage::disk('public');
        $pagePaths = Page::query()
            ->whereHas('chapter', fn ($query) => $query->where('comic_id', $comic->id))
            ->pluck('image_path')
            ->filter()
            ->unique();

        foreach ($pagePaths as $pagePath) {
            $publicDisk->delete($pagePath);
        }

        if (! empty($comic->cover_image)) {
            $publicDisk->delete($comic->cover_image);
        }

        $comic->delete();

        return redirect()->route('admin.comics.index')->with('success', 'Comic deleted successfully.');
    }

    protected function validateComic(Request $request, ?Comic $comic): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:comics,slug' . ($comic ? ',' . $comic->id : '')],
            'description' => ['nullable', 'string'],
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

    protected function handleUploadedMedia(?string $existingPath, $uploadedFile, string $directory, ?string $fallback = null): ?string
    {
        if ($uploadedFile instanceof \Illuminate\Http\UploadedFile) {
            if ($existingPath && Storage::disk('public')->exists($existingPath)) {
                Storage::disk('public')->delete($existingPath);
            }

            return $this->storeMediaFile($uploadedFile, $directory);
        }

        return is_string($fallback) && $fallback !== '' ? $fallback : $existingPath;
    }

    protected function storeMediaFile($uploadedFile, string $directory, ?string $fallback = null): ?string
    {
        if (! $uploadedFile) {
            return is_string($fallback) && $fallback !== '' ? $fallback : null;
        }

        if (! $uploadedFile->isValid()) {
            throw ValidationException::withMessages([
                'cover_image' => ['The uploaded file is invalid.'],
            ]);
        }

        return $uploadedFile->storeAs($directory, $this->buildMediaFilename($uploadedFile, $directory), 'public');
    }

    protected function buildMediaFilename($uploadedFile, string $directory): string
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');

        return 'media_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    }
}
