<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('comics') || ! Schema::hasTable('genres') || ! Schema::hasTable('chapters')) {
            return view('home', [
                'featuredComics' => collect(),
                'latestComics' => collect(),
                'latestChapters' => collect(),
                'genres' => collect(),
            ]);
        }

        $featuredComics = Comic::query()
            ->with('genres')
            ->where('is_featured', true)
            ->published()
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $latestComics = Comic::query()
            ->with('genres')
            ->published()
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $latestChapters = Chapter::query()
            ->with('comic')
            ->where('is_published', true)
            ->whereHas('comic', fn ($query) => $query->published())
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $genres = Genre::query()
            ->withCount('comics')
            ->orderBy('name')
            ->limit(8)
            ->get();

        return view('home', compact('featuredComics', 'latestComics', 'latestChapters', 'genres'));
    }

    public function comics(): View
    {
        if (! Schema::hasTable('comics')) {
            return view('public.comics', ['comics' => collect()]);
        }

        $comics = Comic::query()
            ->with('genres')
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('public.comics', compact('comics'));
    }

    public function genres(): View
    {
        if (! Schema::hasTable('genres')) {
            return view('public.genres', ['genres' => collect()]);
        }

        $genres = Genre::query()
            ->withCount('comics')
            ->orderBy('name')
            ->get();

        return view('public.genres', compact('genres'));
    }

    public function genre(Genre $genre): View
    {
        if (! Schema::hasTable('genres') || ! Schema::hasTable('comic_genres') || ! Schema::hasTable('comics')) {
            return view('public.genres', ['genres' => collect(), 'selectedGenre' => $genre, 'genreComics' => collect()]);
        }

        $genreComics = $genre->comics()
            ->published()
            ->orderByDesc('published_at')
            ->get();

        $genres = Genre::query()
            ->withCount('comics')
            ->orderBy('name')
            ->get();

        return view('public.genres', [
            'selectedGenre' => $genre,
            'genres' => $genres,
            'genreComics' => $genreComics,
        ]);
    }

    public function search(Request $request): View
    {
        if (! Schema::hasTable('comics')) {
            return view('public.search', ['query' => '', 'results' => collect()]);
        }

        $query = trim((string) $request->query('q', ''));

        $results = Comic::query()
            ->with('genres')
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhere('seo_title', 'like', "%{$query}%");
                });
            })
            ->published()
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        return view('public.search', compact('query', 'results'));
    }
}
