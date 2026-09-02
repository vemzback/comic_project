<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use App\Services\ComicPublicationReadiness;
use App\Services\PublicImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function store(
        Request $request,
        Comic $comic,
        Chapter $chapter,
        ComicPublicationReadiness $readiness,
        PublicImageStorage $images,
    ): RedirectResponse {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        $validated = $this->validatePage($request, $chapter, null);

        $imagePath = $images->store(
            $request->file('image_path'),
            'chapters/pages',
            'image_path',
            $request->input('image_path'),
        );

        try {
            DB::transaction(function () use ($chapter, $comic, $validated, $imagePath, $readiness) {
                $chapter->pages()->create([
                    'page_number' => $validated['page_number'],
                    'title' => $validated['title'] ?? null,
                    'image_path' => $imagePath,
                ]);

                $this->ensurePublishedContentRemainsReady($comic, $chapter, $readiness);
            });
        } catch (\Throwable $exception) {
            if ($request->hasFile('image_path') && $imagePath) {
                $images->delete($imagePath);
            }

            throw $exception;
        }

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

    public function update(
        Request $request,
        Comic $comic,
        Chapter $chapter,
        Page $page,
        ComicPublicationReadiness $readiness,
        PublicImageStorage $images,
    ): RedirectResponse {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $this->ensurePageBelongsToChapter($chapter, $page);

        $validated = $this->validatePage($request, $chapter, $page);

        $oldImagePath = $page->image_path;
        $newImagePath = $request->hasFile('image_path')
            ? $images->store($request->file('image_path'), 'chapters/pages', 'image_path')
            : null;
        $requestedImagePath = is_string($request->input('image_path')) && $request->input('image_path') !== ''
            ? $request->input('image_path')
            : null;

        try {
            DB::transaction(function () use ($page, $chapter, $comic, $validated, $newImagePath, $requestedImagePath, $readiness) {
                $page->update([
                    'page_number' => $validated['page_number'],
                    'title' => $validated['title'] ?? null,
                    'image_path' => $newImagePath ?? $requestedImagePath ?? $page->image_path,
                ]);

                $this->ensurePublishedContentRemainsReady($comic, $chapter, $readiness);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath) {
                $images->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath && $oldImagePath && $oldImagePath !== $newImagePath) {
            $images->delete($oldImagePath);
        }

        return redirect()->route('admin.comics.chapters.pages.index', [$comic, $chapter])->with('success', 'Page updated successfully.');
    }

    public function destroy(Comic $comic, Chapter $chapter, Page $page, PublicImageStorage $images): RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $this->ensurePageBelongsToChapter($chapter, $page);

        if ($comic->published_at && $chapter->is_published) {
            throw ValidationException::withMessages([
                'page' => ['Unpublish this chapter or the comic before deleting pages from it. This protects content that is public or scheduled.'],
            ]);
        }

        $images->delete($page->image_path);

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

    protected function ensurePublishedContentRemainsReady(
        Comic $comic,
        Chapter $chapter,
        ComicPublicationReadiness $readiness
    ): void {
        if ($chapter->is_published) {
            $chapterResult = $readiness->evaluateChapter($chapter->fresh('pages'));

            if (! $chapterResult['ready']) {
                throw ValidationException::withMessages([
                    'page_number' => ['This change would make the published chapter incomplete: '.implode(' ', $chapterResult['blockers'])],
                ]);
            }
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
            'image_path' => ['nullable'],
        ];

        if (! $request->hasFile('image_path') && blank($page?->image_path ?? $request->input('image_path'))) {
            $rules['image_path'][] = 'required';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('image_path')) {
            $request->validate([
                'image_path' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            ]);
        }

        return $validated;
    }
}
