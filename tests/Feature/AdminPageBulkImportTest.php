<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class AdminPageBulkImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $temporaryArchives = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryArchives as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_guest_and_regular_user_cannot_access_bulk_import(): void
    {
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        $route = route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter]);

        $this->get($route)->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get($route)
            ->assertForbidden();
    }

    public function test_admin_can_preview_naturally_sorted_pages_and_confirm_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create(['slug' => 'test-comic']);
        $chapter = Chapter::factory()->create([
            'comic_id' => $comic->id,
            'chapter_number' => 1,
        ]);
        $archive = $this->archive([
            '010.png' => $this->png(),
            '002.png' => $this->png(),
            '001.png' => $this->png(),
            'ComicInfo.xml' => '<ComicInfo />',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.comics.chapters.pages.bulk.preview', [$comic, $chapter]),
            ['archive' => $archive],
        );

        $response->assertOk()
            ->assertSee('Review Page Order')
            ->assertSeeInOrder(['001.png', '002.png', '010.png'])
            ->assertSee('pages detected');

        $token = $this->stagedToken();
        $manifest = json_decode(
            Storage::disk('local')->get("chapter-page-imports/{$token}/manifest.json"),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(
            ['001.png', '002.png', '010.png'],
            collect($manifest['pages'])->pluck('original_name')->all(),
        );

        $this->actingAs($admin)
            ->get(route('admin.comics.chapters.pages.bulk.image', [$comic, $chapter, $token, 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($admin)
            ->post(route('admin.comics.chapters.pages.bulk.store', [$comic, $chapter, $token]))
            ->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]))
            ->assertSessionHas('success');

        $this->assertSame([1, 2, 3], $chapter->pages()->orderBy('page_number')->pluck('page_number')->all());

        foreach ($chapter->pages as $page) {
            Storage::disk('public')->assertExists($page->image_path);
            $this->assertStringStartsWith('chapters/test-comic/chapter-1/page-', $page->image_path);
        }

        Storage::disk('local')->assertMissing("chapter-page-imports/{$token}/manifest.json");
    }

    public function test_bulk_import_rejects_an_unsafe_archive_path(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        $archive = $this->archive(['../escape.png' => $this->png()]);

        $this->actingAs($admin)
            ->from(route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter]))
            ->post(route('admin.comics.chapters.pages.bulk.preview', [$comic, $chapter]), ['archive' => $archive])
            ->assertRedirect(route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter]))
            ->assertSessionHasErrors('archive');

        $this->assertSame([], Storage::disk('local')->directories('chapter-page-imports'));
        $this->assertDatabaseCount('pages', 0);
    }

    public function test_bulk_import_rejects_a_fake_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        $archive = $this->archive(['001.png' => '<?php echo "not an image";']);

        $this->actingAs($admin)
            ->from(route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter]))
            ->post(route('admin.comics.chapters.pages.bulk.preview', [$comic, $chapter]), ['archive' => $archive])
            ->assertSessionHasErrors('archive');

        $this->assertDatabaseCount('pages', 0);
        $this->assertSame([], Storage::disk('local')->directories('chapter-page-imports'));
    }

    public function test_bulk_import_is_blocked_when_the_chapter_already_has_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);
        Page::factory()->create(['chapter_id' => $chapter->id]);

        $this->actingAs($admin)
            ->get(route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter]))
            ->assertRedirect(route('admin.comics.chapters.pages.index', [$comic, $chapter]))
            ->assertSessionHasErrors('archive');
    }

    public function test_confirm_rechecks_for_existing_pages_and_keeps_public_storage_clean(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        $this->actingAs($admin)->post(
            route('admin.comics.chapters.pages.bulk.preview', [$comic, $chapter]),
            ['archive' => $this->archive(['001.png' => $this->png()])],
        )->assertOk();

        $token = $this->stagedToken();
        Page::factory()->create(['chapter_id' => $chapter->id, 'page_number' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.comics.chapters.pages.bulk.store', [$comic, $chapter, $token]))
            ->assertSessionHasErrors('archive');

        $this->assertCount(1, $chapter->pages()->get());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_staged_preview_is_scoped_to_the_admin_who_uploaded_it(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $comic->id]);

        $this->actingAs($owner)->post(
            route('admin.comics.chapters.pages.bulk.preview', [$comic, $chapter]),
            ['archive' => $this->archive(['001.png' => $this->png()])],
        )->assertOk();

        $token = $this->stagedToken();

        $this->actingAs($otherAdmin)
            ->get(route('admin.comics.chapters.pages.bulk.image', [$comic, $chapter, $token, 1]))
            ->assertNotFound();

        $this->actingAs($otherAdmin)
            ->post(route('admin.comics.chapters.pages.bulk.store', [$comic, $chapter, $token]))
            ->assertNotFound();
    }

    public function test_bulk_import_rejects_a_chapter_from_another_comic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $comic = Comic::factory()->create();
        $otherComic = Comic::factory()->create();
        $chapter = Chapter::factory()->create(['comic_id' => $otherComic->id]);

        $this->actingAs($admin)
            ->get(route('admin.comics.chapters.pages.bulk.create', [$comic, $chapter]))
            ->assertNotFound();
    }

    /** @param array<string, string> $entries */
    private function archive(array $entries, string $clientName = 'chapter.cbz'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'comic-import-');
        $this->temporaryArchives[] = $path;

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $contents) {
            $this->assertTrue($zip->addFromString($name, $contents));
        }

        $this->assertTrue($zip->close());

        return new UploadedFile($path, $clientName, 'application/zip', null, true);
    }

    private function stagedToken(): string
    {
        $directories = Storage::disk('local')->directories('chapter-page-imports');
        $this->assertCount(1, $directories);

        return Str::afterLast(str_replace('\\', '/', $directories[0]), '/');
    }

    private function png(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
    }
}
