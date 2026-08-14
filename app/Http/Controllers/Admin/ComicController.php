<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $comic = Comic::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'cover_image' => $validated['cover_image'] ?? null,
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

        DB::transaction(function () use ($comic, $validated) {
            $comic->update([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'cover_image' => $validated['cover_image'] ?? null,
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
        $comic->delete();

        return redirect()->route('admin.comics.index')->with('success', 'Comic deleted successfully.');
    }

    protected function validateComic(Request $request, ?Comic $comic): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:comics,slug' . ($comic ? ',' . $comic->id : '')],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:ongoing,completed,hiatus'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];

        return $request->validate($rules);
    }
}
