<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function show(Comic $comic, Chapter $chapter): View
    {
        // Ensure chapter belongs to this comic
        if ($chapter->comic_id !== $comic->id) {
            abort(404);
        }

        // Ensure chapter is published
        if (! $chapter->is_published) {
            abort(404);
        }

        // Load pages ordered by page_number
        $pages = $chapter->pages()
            ->orderBy('page_number')
            ->get();

        // Get previous and next chapters
        $previousChapter = Chapter::where('comic_id', $comic->id)
            ->where('is_published', true)
            ->where(function ($query) use ($chapter) {
                $query->where('sort_order', '<', $chapter->sort_order)
                    ->orWhere(function ($q) use ($chapter) {
                        $q->where('sort_order', '=', $chapter->sort_order)
                            ->where('chapter_number', '<', $chapter->chapter_number);
                    });
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('chapter_number')
            ->first();

        $nextChapter = Chapter::where('comic_id', $comic->id)
            ->where('is_published', true)
            ->where(function ($query) use ($chapter) {
                $query->where('sort_order', '>', $chapter->sort_order)
                    ->orWhere(function ($q) use ($chapter) {
                        $q->where('sort_order', '=', $chapter->sort_order)
                            ->where('chapter_number', '>', $chapter->chapter_number);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('chapter_number')
            ->first();

        return view('public.reader', compact('comic', 'chapter', 'pages', 'previousChapter', 'nextChapter'));
    }
}
