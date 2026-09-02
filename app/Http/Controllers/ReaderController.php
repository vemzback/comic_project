<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function show(Comic $comic, Chapter $chapter): View
    {
        if (! $comic->published_at || $comic->published_at->isFuture()) {
            abort(404);
        }

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

        // Prefer an explicit page, then restore the authenticated reader's saved page.
        $requestedPageNumber = request()->query('page');
        $currentPageNumber = $pages->first()?->page_number;

        if ($requestedPageNumber !== null) {
            $requestedPage = $pages->firstWhere('page_number', (int) $requestedPageNumber);
            $currentPageNumber = $requestedPage?->page_number ?? $currentPageNumber;
        } elseif (Auth::check()) {
            $savedPageNumber = ReadingHistory::query()
                ->where('user_id', Auth::id())
                ->where('comic_id', $comic->id)
                ->where('chapter_id', $chapter->id)
                ->value('page_number');

            $savedPage = $pages->firstWhere('page_number', $savedPageNumber);
            $currentPageNumber = $savedPage?->page_number ?? $currentPageNumber;
        }

        $currentPage = $pages->firstWhere('page_number', $currentPageNumber);

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

    public function progress(Request $request, Comic $comic, Chapter $chapter): JsonResponse
    {
        if (! $comic->published_at || $comic->published_at->isFuture()) {
            abort(404);
        }

        if ($chapter->comic_id !== $comic->id || ! $chapter->is_published) {
            abort(404);
        }

        $validated = $request->validate([
            'page_number' => [
                'required',
                'integer',
                Rule::exists('pages', 'page_number')->where('chapter_id', $chapter->id),
            ],
        ]);

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'comic_id' => $comic->id,
                'chapter_id' => $chapter->id,
            ],
            [
                'page_number' => $validated['page_number'],
                'last_read_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Reading position saved.',
            'page_number' => $history->page_number,
            'last_read_at' => $history->last_read_at->toIso8601String(),
        ]);
    }
}
