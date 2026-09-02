<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ReadingHistory;
use App\Services\ComicPublicationReadiness;
use App\Services\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChapterController extends Controller
{
    public function index(Comic $comic): View
    {
        $chapters = $comic->chapters()->orderBy('sort_order')->orderBy('chapter_number')->paginate(12);

        return view('admin.chapters.index', compact('comic', 'chapters'));
    }

    public function create(Comic $comic): View
    {
        return view('admin.chapters.create', compact('comic'));
    }

    public function store(Request $request, Comic $comic): RedirectResponse
    {
        $validated = $this->validateChapter($request, $comic, null);

        if (! empty($validated['is_published'])) {
            throw ValidationException::withMessages([
                'is_published' => ['Create the chapter as a draft first. Add its pages, then publish it from the Edit page.'],
            ]);
        }

        $comic->chapters()->create([
            'chapter_number' => $validated['chapter_number'],
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => (bool) ($validated['is_published'] ?? false),
            'published_at' => $validated['published_at'] ?? null,
        ]);

        return redirect()->route('admin.comics.chapters.index', $comic)->with('success', 'Chapter created successfully.');
    }

    public function show(Comic $comic, Chapter $chapter): View
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        return view('admin.chapters.show', compact('comic', 'chapter'));
    }

    public function edit(Comic $comic, Chapter $chapter, ComicPublicationReadiness $readiness): View
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $chapterReadiness = $readiness->evaluateChapter($chapter);

        return view('admin.chapters.edit', compact('comic', 'chapter', 'chapterReadiness'));
    }

    public function update(Request $request, Comic $comic, Chapter $chapter, ComicPublicationReadiness $readiness): RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        $validated = $this->validateChapter($request, $comic, $chapter);
        $wasPublished = (bool) $chapter->is_published;

        DB::transaction(function () use ($chapter, $comic, $validated, $readiness, $wasPublished) {
            $chapter->update([
                'chapter_number' => $validated['chapter_number'],
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_published' => (bool) ($validated['is_published'] ?? false),
                'published_at' => $validated['published_at'] ?? null,
            ]);

            if ($chapter->is_published) {
                $result = $readiness->evaluateChapter($chapter->fresh('pages'));

                if (! $result['ready']) {
                    throw ValidationException::withMessages([
                        'is_published' => ['This chapter is not ready to publish: '.implode(' ', $result['blockers'])],
                    ]);
                }
            }

            if ($comic->published_at && $wasPublished && ! $chapter->is_published) {
                $comicResult = $readiness->evaluate($comic->fresh(['genres', 'chapters.pages']));

                if (! $comicResult['ready']) {
                    throw ValidationException::withMessages([
                        'is_published' => ['This change would make the published comic incomplete: '.implode(' ', $comicResult['blockers'])],
                    ]);
                }
            }
        });

        return redirect()->route('admin.comics.chapters.index', $comic)->with('success', 'Chapter updated successfully.');
    }

    public function destroy(Comic $comic, Chapter $chapter, PublicImageStorage $images): RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        if ($comic->published_at && $chapter->is_published) {
            throw ValidationException::withMessages([
                'chapter' => ['Unpublish this chapter or the comic before deleting it. This protects content that is public or scheduled.'],
            ]);
        }

        $this->mergeChapterHistoriesBeforeDelete($comic, $chapter);

        foreach ($chapter->pages as $page) {
            $images->delete($page->image_path);
        }

        $chapter->delete();

        return redirect()->route('admin.comics.chapters.index', $comic)->with('success', 'Chapter deleted successfully.');
    }

    protected function mergeChapterHistoriesBeforeDelete(Comic $comic, Chapter $chapter): void
    {
        $chapterHistories = ReadingHistory::query()
            ->where('comic_id', $comic->id)
            ->where('chapter_id', $chapter->id)
            ->get();

        foreach ($chapterHistories as $chapterHistory) {
            $nullHistory = ReadingHistory::query()
                ->where('user_id', $chapterHistory->user_id)
                ->where('comic_id', $comic->id)
                ->whereNull('chapter_id')
                ->first();

            if (! $nullHistory) {
                continue;
            }

            $chapterIsMoreRecent = collect([$chapterHistory, $nullHistory])
                ->sortByDesc(fn (ReadingHistory $history) => [
                    $history->last_read_at?->getTimestamp() ?? 0,
                    $history->updated_at?->getTimestamp() ?? 0,
                    $history->id,
                ])
                ->first() === $chapterHistory;

            if ($chapterIsMoreRecent) {
                $nullHistory->delete();
                $chapterHistory->chapter_id = null;
                $chapterHistory->save();
            } else {
                $chapterHistory->delete();
            }
        }
    }

    protected function ensureChapterBelongsToComic(Comic $comic, Chapter $chapter): void
    {
        if ((int) $chapter->comic_id !== (int) $comic->id) {
            abort(404);
        }
    }

    protected function validateChapter(Request $request, Comic $comic, ?Chapter $chapter): array
    {
        $rules = [
            'chapter_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('chapters', 'chapter_number')
                    ->where(fn ($query) => $query->where('comic_id', $comic->id))
                    ->ignore($chapter?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('chapters', 'slug')
                    ->where(fn ($query) => $query->where('comic_id', $comic->id))
                    ->ignore($chapter?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];

        return $request->validate($rules);
    }
}
