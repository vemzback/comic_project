<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class ChapterPageArchiveImporter
{
    private const STAGING_ROOT = 'chapter-page-imports';

    private const STAGING_TTL_MINUTES = 60;

    private const MAX_PAGE_COUNT = 300;

    private const MAX_PAGE_BYTES = 12 * 1024 * 1024;

    private const MAX_TOTAL_UNCOMPRESSED_BYTES = 250 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /** @return array<string, mixed> */
    public function stage(UploadedFile $uploadedFile, int $userId, Comic $comic, Chapter $chapter): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'archive' => 'ZIP support is not available on this server.',
            ]);
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (! in_array($extension, ['zip', 'cbz'], true)) {
            throw ValidationException::withMessages([
                'archive' => 'Upload a ZIP or CBZ archive.',
            ]);
        }

        $token = (string) Str::uuid();
        $stageDirectory = $this->stageDirectory($token);
        $zip = new ZipArchive;
        $opened = $zip->open($uploadedFile->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'archive' => 'The archive could not be opened. Make sure it is a valid ZIP or CBZ file.',
            ]);
        }

        try {
            $entries = $this->collectImageEntries($zip);
            $pages = $this->stageImages($zip, $entries, $stageDirectory);

            $manifest = [
                'token' => $token,
                'user_id' => $userId,
                'comic_id' => $comic->id,
                'chapter_id' => $chapter->id,
                'created_at' => now()->toIso8601String(),
                'expires_at' => now()->addMinutes(self::STAGING_TTL_MINUTES)->toIso8601String(),
                'pages' => $pages,
            ];

            $written = Storage::disk('local')->put(
                $this->manifestPath($token),
                json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            );

            if (! $written) {
                throw new RuntimeException('The import manifest could not be stored.');
            }

            return $manifest;
        } catch (Throwable $exception) {
            Storage::disk('local')->deleteDirectory($stageDirectory);
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    /** @return array<string, mixed> */
    public function manifest(string $token, int $userId, Comic $comic, Chapter $chapter): array
    {
        if (! Str::isUuid($token) || ! Storage::disk('local')->exists($this->manifestPath($token))) {
            abort(404);
        }

        $manifest = json_decode(
            Storage::disk('local')->get($this->manifestPath($token)),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (
            (int) ($manifest['user_id'] ?? 0) !== $userId
            || (int) ($manifest['comic_id'] ?? 0) !== (int) $comic->id
            || (int) ($manifest['chapter_id'] ?? 0) !== (int) $chapter->id
        ) {
            abort(404);
        }

        if (now()->isAfter($manifest['expires_at'] ?? now()->subSecond())) {
            $this->discard($token);

            throw ValidationException::withMessages([
                'archive' => 'This preview has expired. Upload the archive again.',
            ]);
        }

        return $manifest;
    }

    /** @return array<string, mixed> */
    public function previewPage(string $token, int $userId, Comic $comic, Chapter $chapter, int $position): array
    {
        $manifest = $this->manifest($token, $userId, $comic, $chapter);
        $page = collect($manifest['pages'] ?? [])->firstWhere('position', $position);

        if (! is_array($page) || ! Storage::disk('local')->exists($page['staged_path'] ?? '')) {
            abort(404);
        }

        return $page;
    }

    /** @return array<int, Page> */
    public function commit(string $token, int $userId, Comic $comic, Chapter $chapter): array
    {
        $manifest = $this->manifest($token, $userId, $comic, $chapter);
        $storedPaths = [];

        try {
            $pages = DB::transaction(function () use ($manifest, $comic, $chapter, &$storedPaths): array {
                $lockedChapter = Chapter::query()->lockForUpdate()->findOrFail($chapter->id);

                if ($lockedChapter->pages()->exists()) {
                    throw ValidationException::withMessages([
                        'archive' => 'Bulk import is only available for chapters that do not have pages yet.',
                    ]);
                }

                $directory = 'chapters/'.Str::slug($comic->slug ?: $comic->title).'/chapter-'.$chapter->chapter_number;
                $createdPages = [];

                foreach ($manifest['pages'] ?? [] as $page) {
                    $position = (int) $page['position'];
                    $extension = (string) $page['extension'];
                    $destination = $directory.'/page-'.str_pad((string) $position, 3, '0', STR_PAD_LEFT).'-'.Str::lower(Str::random(8)).'.'.$extension;
                    $stream = Storage::disk('local')->readStream($page['staged_path']);

                    if (! is_resource($stream)) {
                        throw new RuntimeException('A staged page could not be read.');
                    }

                    try {
                        if (! Storage::disk('public')->writeStream($destination, $stream)) {
                            throw new RuntimeException('A page image could not be stored.');
                        }
                    } finally {
                        fclose($stream);
                    }

                    $storedPaths[] = $destination;
                    $createdPages[] = $lockedChapter->pages()->create([
                        'page_number' => $position,
                        'title' => null,
                        'image_path' => $destination,
                    ]);
                }

                return $createdPages;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        $this->discard($token);

        return $pages;
    }

    public function discard(string $token): void
    {
        if (Str::isUuid($token)) {
            Storage::disk('local')->deleteDirectory($this->stageDirectory($token));
        }
    }

    public function cleanupExpired(): void
    {
        foreach (Storage::disk('local')->directories(self::STAGING_ROOT) as $directory) {
            $token = basename(str_replace('\\', '/', $directory));
            $manifestPath = $this->manifestPath($token);

            if (! Storage::disk('local')->exists($manifestPath)) {
                continue;
            }

            try {
                $manifest = json_decode(Storage::disk('local')->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                if (now()->isAfter($manifest['expires_at'] ?? now()->subSecond())) {
                    $this->discard($token);
                }
            } catch (Throwable) {
                $this->discard($token);
            }
        }
    }

    /** @return array<int, array{index: int, name: string, extension: string, size: int}> */
    private function collectImageEntries(ZipArchive $zip): array
    {
        $entries = [];
        $totalBytes = 0;
        $baseNames = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }

            $this->rejectUnsafePath($name);

            if ($this->isIgnorableMetadataFile($name)) {
                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! array_key_exists($extension, self::ALLOWED_MIME_TYPES)) {
                throw ValidationException::withMessages([
                    'archive' => "Unsupported file in archive: {$name}. Only JPG, PNG, and WebP images are allowed.",
                ]);
            }

            $size = (int) ($stat['size'] ?? 0);
            if ($size < 1 || $size > self::MAX_PAGE_BYTES) {
                throw ValidationException::withMessages([
                    'archive' => "Page {$name} is empty or larger than 12 MB.",
                ]);
            }

            $totalBytes += $size;
            if ($totalBytes > self::MAX_TOTAL_UNCOMPRESSED_BYTES) {
                throw ValidationException::withMessages([
                    'archive' => 'The archive expands beyond the 250 MB safety limit.',
                ]);
            }

            $baseName = mb_strtolower(pathinfo($name, PATHINFO_FILENAME));
            if (isset($baseNames[$baseName])) {
                throw ValidationException::withMessages([
                    'archive' => "Duplicate page filename detected: {$name}.",
                ]);
            }
            $baseNames[$baseName] = true;

            $entries[] = compact('index', 'name', 'extension', 'size');
        }

        if ($entries === []) {
            throw ValidationException::withMessages([
                'archive' => 'No supported page images were found in the archive.',
            ]);
        }

        if (count($entries) > self::MAX_PAGE_COUNT) {
            throw ValidationException::withMessages([
                'archive' => 'An archive may contain at most 300 page images.',
            ]);
        }

        usort($entries, fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

        return $entries;
    }

    /**
     * @param  array<int, array{index: int, name: string, extension: string, size: int}>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function stageImages(ZipArchive $zip, array $entries, string $stageDirectory): array
    {
        $pages = [];

        foreach ($entries as $offset => $entry) {
            $position = $offset + 1;
            $stagedPath = $stageDirectory.'/pages/'.str_pad((string) $position, 3, '0', STR_PAD_LEFT).'.'.$entry['extension'];
            $stream = $zip->getStream($entry['name']);

            if (! is_resource($stream)) {
                throw ValidationException::withMessages([
                    'archive' => "Page {$entry['name']} could not be extracted.",
                ]);
            }

            try {
                if (! Storage::disk('local')->writeStream($stagedPath, $stream)) {
                    throw new RuntimeException('A staged page could not be stored.');
                }
            } finally {
                fclose($stream);
            }

            $absolutePath = Storage::disk('local')->path($stagedPath);
            $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolutePath);
            $imageSize = @getimagesize($absolutePath);
            $expectedMime = self::ALLOWED_MIME_TYPES[$entry['extension']];

            if ($detectedMime !== $expectedMime || $imageSize === false) {
                throw ValidationException::withMessages([
                    'archive' => "File {$entry['name']} is not a valid {$entry['extension']} image.",
                ]);
            }

            $pages[] = [
                'position' => $position,
                'original_name' => $entry['name'],
                'staged_path' => $stagedPath,
                'extension' => $entry['extension'],
                'mime' => $detectedMime,
                'size' => $entry['size'],
                'width' => $imageSize[0],
                'height' => $imageSize[1],
            ];
        }

        return $pages;
    }

    private function rejectUnsafePath(string $name): void
    {
        $segments = explode('/', $name);
        $unsafe = str_contains($name, "\0")
            || str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:/', $name)
            || in_array('..', $segments, true);

        if ($unsafe) {
            throw ValidationException::withMessages([
                'archive' => 'The archive contains an unsafe file path and was rejected.',
            ]);
        }
    }

    private function isIgnorableMetadataFile(string $name): bool
    {
        $normalized = mb_strtolower($name);
        $baseName = mb_strtolower(basename($name));

        return str_starts_with($normalized, '__macosx/')
            || str_starts_with($baseName, '.')
            || in_array($baseName, ['comicinfo.xml', 'thumbs.db'], true);
    }

    private function stageDirectory(string $token): string
    {
        return self::STAGING_ROOT.'/'.$token;
    }

    private function manifestPath(string $token): string
    {
        return $this->stageDirectory($token).'/manifest.json';
    }
}
