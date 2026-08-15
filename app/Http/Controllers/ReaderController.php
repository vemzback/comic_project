<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ReadingHistory;
use Illuminate\Support\Facades\Auth;
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

        // Determine current page
        $currentPageNumber = request()->query('page', null);
        $currentPage = null;

        if ($currentPageNumber !== null) {
            // Validate page is within chapter
            $currentPage = $pages->firstWhere('page_number', (int) $currentPageNumber);
            if ($currentPage === null) {
                // Invalid page number, default to first page
                $currentPageNumber = null;
            }
        }

        // If authenticated, record reading history
        if (Auth::check()) {
            $pageNumberToRecord = $currentPageNumber ? (int) $currentPageNumber : ($pages->first()?->page_number ?? 1);

            ReadingHistory::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'comic_id' => $comic->id,
                    'chapter_id' => $chapter->id,
                ],
                [
                    'page_number' => $pageNumberToRecord,
                    'last_read_at' => now(),
                ]
            );
        }

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

        return view('public.reader', compact('comic', 'chapter', 'pages', 'previousChapter', 'nextChapter', 'currentPageNumber', 'currentPage'));
    }
}
