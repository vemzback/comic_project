<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('comics')
            ->orderBy('name')
            ->paginate(12);

        return view('admin.genres.index', compact('genres'));
    }

    public function create(): View
    {
        return view('admin.genres.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGenre($request);

        DB::transaction(function () use ($validated): void {
            Genre::create([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'description' => $validated['description'] ?? null,
            ]);
        });

        return redirect()->route('admin.genres.index')->with('success', 'Genre created successfully.');
    }

    public function edit(Genre $genre): View
    {
        return view('admin.genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $validated = $this->validateGenre($request, $genre);

        DB::transaction(function () use ($validated, $genre): void {
            $genre->update([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name'], $genre),
                'description' => $validated['description'] ?? null,
            ]);
        });

        return redirect()->route('admin.genres.index')->with('success', 'Genre updated successfully.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('success', 'Genre deleted successfully.');
    }

    protected function validateGenre(Request $request, ?Genre $genre = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'name')->ignore($genre),
            ],
            'description' => ['nullable', 'string'],
        ]);

        if (Str::slug($validated['name']) === '') {
            throw ValidationException::withMessages([
                'name' => 'The genre name must contain at least one letter or number.',
            ]);
        }

        return $validated;
    }

    protected function uniqueSlug(string $name, ?Genre $genre = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (Genre::query()
            ->where('slug', $slug)
            ->when($genre, fn ($query) => $query->whereKeyNot($genre->getKey()))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
