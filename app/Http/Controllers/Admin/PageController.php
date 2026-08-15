<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Comic $comic, Chapter $chapter): View
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        $pages = $chapter->pages()->orderBy('page_number')->paginate(12);

        return view('admin.pages.index', compact('comic', 'chapter', 'pages'));
    }

    public function create(Comic $comic, Chapter $chapter): View
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        return view('admin.pages.create', compact('comic', 'chapter'));
    }

    public function store(Request $request, Comic $comic, Chapter $chapter): RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        $validated = $this->validatePage($request, $chapter, null);

        $chapter->pages()->create([
            'page_number' => $validated['page_number'],
            'title' => $validated['title'] ?? null,
            'image_path' => $validated['image_path'],
        ]);

        return redirect()->route('admin.comics.chapters.pages.index', [$comic, $chapter])->with('success', 'Page created successfully.');
    }

    public function show(Comic $comic, Chapter $chapter, Page $page): View
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $this->ensurePageBelongsToChapter($chapter, $page);

        return view('admin.pages.show', compact('comic', 'chapter', 'page'));
    }

    public function edit(Comic $comic, Chapter $chapter, Page $page): View
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $this->ensurePageBelongsToChapter($chapter, $page);

        return view('admin.pages.edit', compact('comic', 'chapter', 'page'));
    }

    public function update(Request $request, Comic $comic, Chapter $chapter, Page $page): RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $this->ensurePageBelongsToChapter($chapter, $page);

        $validated = $this->validatePage($request, $chapter, $page);

        $page->update([
            'page_number' => $validated['page_number'],
            'title' => $validated['title'] ?? null,
            'image_path' => $validated['image_path'],
        ]);

        return redirect()->route('admin.comics.chapters.pages.index', [$comic, $chapter])->with('success', 'Page updated successfully.');
    }

    public function destroy(Comic $comic, Chapter $chapter, Page $page): RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $this->ensurePageBelongsToChapter($chapter, $page);

        $page->delete();

        return redirect()->route('admin.comics.chapters.pages.index', [$comic, $chapter])->with('success', 'Page deleted successfully.');
    }

    protected function ensureChapterBelongsToComic(Comic $comic, Chapter $chapter): void
    {
        if ((int) $chapter->comic_id !== (int) $comic->id) {
            abort(404);
        }
    }

    protected function ensurePageBelongsToChapter(Chapter $chapter, Page $page): void
    {
        if ((int) $page->chapter_id !== (int) $chapter->id) {
            abort(404);
        }
    }

    protected function validatePage(Request $request, Chapter $chapter, ?Page $page): array
    {
        $rules = [
            'page_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('pages', 'page_number')
                    ->where(fn ($query) => $query->where('chapter_id', $chapter->id))
                    ->ignore($page?->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'image_path' => ['required', 'string', 'max:255'],
        ];

        return $request->validate($rules);
    }
}
