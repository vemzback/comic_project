<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use Illuminate\View\View;

class ComicDetailController extends Controller
{
    public function show(Comic $comic): View
    {
        if (! $comic->published_at || $comic->published_at->isFuture()) {
            abort(404);
        }

        $comic->load([
            'chapters' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('sort_order')
                    ->orderBy('chapter_number');
            },
            'genres',
            'comments' => function ($query) {
                $query->where('is_approved', true)
                    ->with('user')
                    ->latest();
            },
        ]);

        $publishedChaptersCount = $comic->chapters->count();

        $userRating = auth()->check() ? $comic->ratings()->where('user_id', auth()->id())->first() : null;
        $averageRating = $comic->ratings()->average('score');
        $ratingCount = $comic->ratings()->count();

        return view('public.comic-detail', compact('comic', 'publishedChaptersCount', 'userRating', 'averageRating', 'ratingCount'));
    }
}
