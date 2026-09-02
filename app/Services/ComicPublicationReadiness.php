<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Support\Facades\Storage;

class ComicPublicationReadiness
{
    /**
     * Evaluate whether a comic is safe to publish.
     *
     * @return array{
     *     ready: bool,
     *     checks: array<int, array{key: string, label: string, passed: bool, message: string}>,
     *     blockers: array<int, string>,
     *     warnings: array<int, string>
     * }
     */
    public function evaluate(Comic $comic): array
    {
        $comic->loadMissing([
            'genres:id,name',
            'chapters.pages',
        ]);

        $publishedChapters = $comic->chapters
            ->where('is_published', true)
            ->sortBy('chapter_number')
            ->values();

        $checks = [
            $this->check(
                'description',
                'Description',
                filled($comic->description),
                'Add a description so readers know what the comic is about.'
            ),
            $this->check(
                'author',
                'Author',
                filled($comic->author),
                'Add the author or creator name.'
            ),
            $this->check(
                'cover',
                'Cover image',
                $this->publicFileExists($comic->cover_image),
                'Upload a valid cover image.'
            ),
            $this->check(
                'genres',
                'Genres',
                $comic->genres->isNotEmpty(),
                'Assign at least one genre.'
            ),
            $this->check(
                'published_chapter',
                'Published chapter',
                $publishedChapters->isNotEmpty(),
                'Publish at least one complete chapter.'
            ),
        ];

        $chapterBlockers = $publishedChapters
            ->flatMap(function (Chapter $chapter): array {
                return collect($this->evaluateChapter($chapter)['blockers'])
                    ->map(fn (string $message): string => "Chapter {$chapter->chapter_number}: {$message}")
                    ->all();
            })
            ->values()
            ->all();

        $checks[] = [
            'key' => 'chapter_content',
            'label' => 'Chapter content',
            'passed' => $publishedChapters->isNotEmpty() && $chapterBlockers === [],
            'message' => $chapterBlockers === []
                ? 'All published chapters have valid, sequential pages.'
                : implode(' ', $chapterBlockers),
        ];

        $blockers = collect($checks)
            ->where('passed', false)
            ->pluck('message')
            ->values()
            ->all();

        $warnings = collect([
            filled($comic->publisher) ? null : 'Publisher information has not been added.',
            filled($comic->seo_title) ? null : 'SEO title has not been added.',
            filled($comic->seo_description) ? null : 'SEO description has not been added.',
        ])->filter()->values()->all();

        return [
            'ready' => $blockers === [],
            'checks' => $checks,
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    /**
     * Evaluate whether a chapter is safe to publish.
     *
     * @return array{ready: bool, blockers: array<int, string>}
     */
    public function evaluateChapter(Chapter $chapter): array
    {
        $chapter->loadMissing('pages');

        $pages = $chapter->pages->sortBy('page_number')->values();
        $blockers = [];

        if (! $chapter->published_at) {
            $blockers[] = 'set a publication date.';
        }

        if ($pages->isEmpty()) {
            $blockers[] = 'add at least one page.';
        } else {
            $actualNumbers = $pages->pluck('page_number')->map(fn ($number): int => (int) $number)->all();
            $expectedNumbers = range(1, $pages->count());

            if ($actualNumbers !== $expectedNumbers) {
                $blockers[] = 'page numbers must start at 1 and remain sequential without gaps.';
            }

            if ($pages->contains(fn ($page): bool => ! $this->publicFileExists($page->image_path))) {
                $blockers[] = 'one or more page images are missing from storage.';
            }
        }

        return [
            'ready' => $blockers === [],
            'blockers' => $blockers,
        ];
    }

    /**
     * @return array{key: string, label: string, passed: bool, message: string}
     */
    private function check(string $key, string $label, bool $passed, string $failureMessage): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'passed' => $passed,
            'message' => $passed ? 'Complete.' : $failureMessage,
        ];
    }

    private function publicFileExists(?string $path): bool
    {
        return filled($path) && Storage::disk('public')->exists($path);
    }
}
