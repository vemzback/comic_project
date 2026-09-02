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
                $query->whereNull('parent_id')
                    ->where('is_approved', true)
                    ->with([
                        'user',
                        'replies' => fn ($replyQuery) => $replyQuery
                            ->where('is_approved', true)
                            ->with('user')
                            ->oldest(),
                    ])
                    ->latest();
            },
        ]);

        $publishedChaptersCount = $comic->chapters->count();

        $userRating = auth()->check() ? $comic->ratings()->where('user_id', auth()->id())->first() : null;
        $averageRating = $comic->ratings()->average('score');
        $ratingCount = $comic->ratings()->count();
        $continueReadingHistory = auth()->check()
            ? auth()->user()->readingHistories()
                ->with('chapter')
                ->where('comic_id', $comic->id)
                ->whereNotNull('chapter_id')
                ->whereHas('chapter', fn ($query) => $query->where('is_published', true))
                ->latest('last_read_at')
                ->first()
            : null;

        return view('public.comic-detail', compact('comic', 'publishedChaptersCount', 'userRating', 'averageRating', 'ratingCount', 'continueReadingHistory'));
    }
}
