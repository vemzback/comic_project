<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComicMetadataImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_admin_can_access_metadata_import(): void
    {
        $this->get(route('admin.comics.import.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.comics.import.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.comics.import.index'))
            ->assertOk()
            ->assertSee('Import Metadata')
            ->assertSee('AniList (Manga)')
            ->assertSee('Google Books');
    }

    public function test_admin_can_search_anilist_metadata(): void
    {
        $this->fakeAniList();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.comics.import.index', [
                'provider' => 'anilist',
                'q' => 'Berserk',
            ]))
            ->assertOk()
            ->assertSee('Berserk')
            ->assertSee('Kentaro Miura')
            ->assertSee('Import as Draft');
    }

    public function test_admin_can_import_anilist_metadata_cover_and_matching_genres_as_draft(): void
    {
        Storage::fake('public');
        $this->fakeAniList();
        $admin = User::factory()->create(['role' => 'admin']);
        $action = Genre::factory()->create(['name' => 'Action', 'slug' => 'action']);

        $this->actingAs($admin)
            ->post(route('admin.comics.import.store'), [
                'provider' => 'anilist',
                'external_id' => '2',
            ])
            ->assertRedirect();

        $comic = Comic::where('external_provider', 'anilist')->where('external_id', '2')->firstOrFail();

        $this->assertSame('Berserk', $comic->title);
        $this->assertSame('Kentaro Miura', $comic->author);
        $this->assertSame('completed', $comic->status);
        $this->assertNull($comic->published_at);
        $this->assertSame('1989-08-25', $comic->original_published_at->format('Y-m-d'));
        $this->assertTrue($comic->genres->contains($action));
        $this->assertNotNull($comic->cover_image);
        Storage::disk('public')->assertExists($comic->cover_image);
    }

    public function test_same_provider_entry_cannot_be_imported_twice(): void
    {
        Storage::fake('public');
        $this->fakeAniList();
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = ['provider' => 'anilist', 'external_id' => '2'];

        $this->actingAs($admin)->post(route('admin.comics.import.store'), $payload)->assertRedirect();
        $this->actingAs($admin)
            ->post(route('admin.comics.import.store'), $payload)
            ->assertSessionHasErrors('import');

        $this->assertSame(1, Comic::where('external_provider', 'anilist')->where('external_id', '2')->count());
    }

    public function test_admin_can_search_google_books_metadata(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [[
                    'id' => 'google-volume-1',
                    'volumeInfo' => [
                        'title' => 'Batman: Year One',
                        'authors' => ['Frank Miller'],
                        'publisher' => 'DC Comics',
                        'publishedDate' => '1987',
                        'description' => 'A defining Batman story.',
                        'categories' => ['Comics & Graphic Novels'],
                        'canonicalVolumeLink' => 'https://books.google.com/books?id=google-volume-1',
                    ],
                ]],
            ]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.comics.import.index', [
                'provider' => 'google_books',
                'q' => 'Batman Year One',
            ]))
            ->assertOk()
            ->assertSee('Batman: Year One')
            ->assertSee('Frank Miller');
    }

    public function test_google_books_import_stores_preview_access_metadata(): void
    {
        Storage::fake('public');
        Http::fake(function (Request $request) {
            if (str_starts_with($request->url(), 'https://www.googleapis.com/books/v1/volumes/google-volume-1')) {
                return Http::response([
                    'id' => 'google-volume-1',
                    'volumeInfo' => [
                        'title' => 'Batman: Year One',
                        'authors' => ['Frank Miller'],
                        'imageLinks' => [
                            'thumbnail' => 'http://books.google.com/books/content?id=google-volume-1&printsec=frontcover&img=1&zoom=1',
                        ],
                        'canonicalVolumeLink' => 'https://books.google.com/books?id=google-volume-1',
                    ],
                    'accessInfo' => [
                        'embeddable' => true,
                        'viewability' => 'PARTIAL',
                        'accessViewStatus' => 'SAMPLE',
                        'publicDomain' => false,
                        'webReaderLink' => 'http://play.google.com/books/reader?id=google-volume-1',
                        'epub' => ['isAvailable' => true],
                        'pdf' => ['isAvailable' => false],
                    ],
                ]);
            }

            if (str_starts_with($request->url(), 'https://books.google.com/books/content')) {
                return Http::response('fake-jpeg-cover', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response([], 404);
        });

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.comics.import.store'), [
                'provider' => 'google_books',
                'external_id' => 'google-volume-1',
            ])
            ->assertRedirect();

        $comic = Comic::where('external_provider', 'google_books')->firstOrFail();

        $this->assertTrue(data_get($comic->source_metadata, 'preview.embeddable'));
        $this->assertSame('PARTIAL', data_get($comic->source_metadata, 'preview.viewability'));
        $this->assertSame('SAMPLE', data_get($comic->source_metadata, 'preview.access_view_status'));
        $this->assertSame(
            'https://play.google.com/books/reader?id=google-volume-1',
            data_get($comic->source_metadata, 'preview.web_reader_url')
        );
        $this->assertTrue(data_get($comic->source_metadata, 'preview.epub_available'));
        $this->assertFalse(data_get($comic->source_metadata, 'preview.pdf_available'));
    }

    public function test_provider_failure_shows_a_safe_admin_message(): void
    {
        Http::fake([
            'https://graphql.anilist.co' => Http::response(['message' => 'Unavailable'], 503),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.comics.import.index', [
                'provider' => 'anilist',
                'q' => 'Berserk',
            ]))
            ->assertOk()
            ->assertSee('The metadata provider could not be reached.');
    }

    private function fakeAniList(): void
    {
        $media = [
            'id' => 2,
            'title' => ['romaji' => 'Berserk', 'english' => 'Berserk', 'native' => 'ベルセルク'],
            'description' => 'A dark fantasy manga.',
            'status' => 'FINISHED',
            'startDate' => ['year' => 1989, 'month' => 8, 'day' => 25],
            'genres' => ['Action', 'Drama', 'Fantasy'],
            'coverImage' => ['extraLarge' => 'https://s4.anilist.co/file/anilistcdn/media/manga/cover/large/bx2.jpg'],
            'siteUrl' => 'https://anilist.co/manga/2/Berserk',
            'staff' => [
                'edges' => [[
                    'role' => 'Story & Art',
                    'node' => ['name' => ['full' => 'Kentaro Miura']],
                ]],
            ],
        ];

        Http::fake(function (Request $request) use ($media) {
            if ($request->url() === 'https://graphql.anilist.co') {
                $variables = $request->data()['variables'] ?? [];

                return isset($variables['search'])
                    ? Http::response(['data' => ['Page' => ['media' => [$media]]]])
                    : Http::response(['data' => ['Media' => $media]]);
            }

            if (str_starts_with($request->url(), 'https://s4.anilist.co/')) {
                return Http::response('fake-jpeg-cover', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response([], 404);
        });
    }
}
