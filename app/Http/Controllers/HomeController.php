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
                'continueReading' => collect(),
            ]);
        }

        $featuredComics = Comic::query()
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->where('is_featured', true)
            ->published()
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $latestComics = Comic::query()
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->published()
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $continueReading = collect();
        $latestChapters = collect();

        if (auth()->check()) {
            if (Schema::hasTable('reading_history')) {
                $continueReading = auth()->user()
                    ->readingHistories()
                    ->with(['comic', 'chapter'])
                    ->whereNotNull('chapter_id')
                    ->whereHas('comic', fn ($query) => $query->published())
                    ->whereHas('chapter', fn ($query) => $query->where('is_published', true))
                    ->latest('last_read_at')
                    ->get()
                    ->unique('comic_id')
                    ->take(5)
                    ->values();
            }

            if ($continueReading->isEmpty()) {
                $latestChapters = Chapter::query()
                    ->with('comic')
                    ->where('is_published', true)
                    ->whereHas('comic', fn ($query) => $query->published())
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get();
            }
        }

        return view('home', compact('featuredComics', 'latestComics', 'latestChapters', 'continueReading'));
    }

    public function comics(): View
    {
        if (! Schema::hasTable('comics')) {
            return view('public.comics', [
                'comics' => collect(),
                'heroComics' => collect(),
            ]);
        }

        $heroComics = Comic::query()
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->published()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $comics = Comic::query()
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('public.comics', compact('comics', 'heroComics'));
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
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
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
            return view('public.search', [
                'query' => '',
                'results' => collect(),
                'topComics' => collect(),
            ]);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $query = trim((string) ($validated['q'] ?? ''));

        $results = Comic::query()
            ->with('genres')
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
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

        $topComics = $query === ''
            ? Comic::query()
                ->withAvg('ratings', 'score')
                ->withCount('ratings')
                ->published()
                ->orderByDesc('ratings_avg_score')
                ->orderByDesc('ratings_count')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get()
            : collect();

        return view('public.search', compact('query', 'results', 'topComics'));
    }
}
