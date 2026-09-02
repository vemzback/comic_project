<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ChapterPageArchiveImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PageBulkImportController extends Controller
{
    public function create(Comic $comic, Chapter $chapter, ChapterPageArchiveImporter $importer): View|RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $importer->cleanupExpired();

        if ($chapter->pages()->exists()) {
            return redirect()->route('admin.comics.chapters.pages.index', [$comic, $chapter])
                ->withErrors(['archive' => 'Bulk import is only available for chapters that do not have pages yet.']);
        }

        return view('admin.pages.bulk-import', compact('comic', 'chapter'));
    }

    public function preview(Request $request, Comic $comic, Chapter $chapter, ChapterPageArchiveImporter $importer): View|RedirectResponse
    {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        if ($chapter->pages()->exists()) {
            throw ValidationException::withMessages([
                'archive' => 'Bulk import is only available for chapters that do not have pages yet.',
            ]);
        }

        $request->validate([
            'archive' => ['required', 'file', 'max:38912'],
        ]);

        try {
            $manifest = $importer->stage($request->file('archive'), (int) $request->user()->id, $comic, $chapter);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'archive' => 'The archive could not be prepared. Check the file and try again.',
            ]);
        }

        return view('admin.pages.bulk-import-preview', compact('comic', 'chapter', 'manifest'));
    }

    public function image(
        Request $request,
        Comic $comic,
        Chapter $chapter,
        string $token,
        int $position,
        ChapterPageArchiveImporter $importer,
    ): StreamedResponse {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $page = $importer->previewPage($token, (int) $request->user()->id, $comic, $chapter, $position);

        return Storage::disk('local')->response(
            $page['staged_path'],
            basename($page['original_name']),
            [
                'Content-Type' => $page['mime'],
                'Cache-Control' => 'private, max-age=300',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function store(
        Request $request,
        Comic $comic,
        Chapter $chapter,
        string $token,
        ChapterPageArchiveImporter $importer,
    ): RedirectResponse {
        $this->ensureChapterBelongsToComic($comic, $chapter);

        try {
            $pages = $importer->commit($token, (int) $request->user()->id, $comic, $chapter);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'archive' => 'The pages could not be imported. No partial page data was kept.',
            ]);
        }

        return redirect()->route('admin.comics.chapters.pages.index', [$comic, $chapter])
            ->with('success', count($pages).' pages imported successfully. Review them before publishing the chapter.');
    }

    public function destroy(
        Request $request,
        Comic $comic,
        Chapter $chapter,
        string $token,
        ChapterPageArchiveImporter $importer,
    ): RedirectResponse {
        $this->ensureChapterBelongsToComic($comic, $chapter);
        $importer->manifest($token, (int) $request->user()->id, $comic, $chapter);
        $importer->discard($token);

        return redirect()->route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter])
            ->with('success', 'The staged archive was discarded.');
    }

    private function ensureChapterBelongsToComic(Comic $comic, Chapter $chapter): void
    {
        if ((int) $chapter->comic_id !== (int) $comic->id) {
            abort(404);
        }
    }
}
