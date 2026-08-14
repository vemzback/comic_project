<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\Page;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComicPlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@comic.test'],
            [
                'name' => 'Comic Admin',
                'password' => Hash::make('Admin@123!'),
                'role' => 'admin',
            ]
        );

        $reader = User::updateOrCreate(
            ['email' => 'reader@comic.test'],
            [
                'name' => 'Comic Reader',
                'password' => Hash::make('Reader@123!'),
                'role' => 'user',
            ]
        );

        $genreData = [
            ['name' => 'Action', 'slug' => 'action', 'description' => 'Fast paced stories with intense battles and high stakes.'],
            ['name' => 'Adventure', 'slug' => 'adventure', 'description' => 'Exploration, quests, and journeys into unknown worlds.'],
            ['name' => 'Fantasy', 'slug' => 'fantasy', 'description' => 'Magic, mythic worlds, and legendary creatures.'],
            ['name' => 'Horror', 'slug' => 'horror', 'description' => 'Dark atmospheres, dread, and terrifying encounters.'],
            ['name' => 'Romance', 'slug' => 'romance', 'description' => 'Emotional connections and relationship-driven stories.'],
            ['name' => 'Comedy', 'slug' => 'comedy', 'description' => 'Lighthearted stories meant to entertain and amuse.'],
        ];

        $genres = [];
        foreach ($genreData as $genreItem) {
            $genre = Genre::updateOrCreate(
                ['slug' => $genreItem['slug']],
                [
                    'name' => $genreItem['name'],
                    'description' => $genreItem['description'],
                ]
            );

            $genres[$genreItem['slug']] = $genre;
        }

        $comicData = [
            [
                'title' => 'Shadow of the Neon City',
                'slug' => 'shadow-of-the-neon-city',
                'description' => 'A cyberpunk bounty hunter uncovers a conspiracy hidden beneath the glowing streets of a megacity.',
                'cover_image' => 'covers/shadow-of-the-neon-city.jpg',
                'status' => 'ongoing',
                'published_at' => '2026-01-15 00:00:00',
                'is_featured' => true,
                'seo_title' => 'Shadow of the Neon City',
                'seo_description' => 'A gritty cyberpunk comic about survival, secrets, and heroism in a futuristic city.',
                'genre_slugs' => ['Action', 'Adventure', 'Fantasy'],
            ],
            [
                'title' => 'Moonlit Hollow',
                'slug' => 'moonlit-hollow',
                'description' => 'A quiet village wakes to strange legends when the moon begins to bleed across the valley.',
                'cover_image' => 'covers/moonlit-hollow.jpg',
                'status' => 'completed',
                'published_at' => '2025-11-10 00:00:00',
                'is_featured' => false,
                'seo_title' => 'Moonlit Hollow',
                'seo_description' => 'A supernatural adventure comic filled with mystery, horror, and ancient curses.',
                'genre_slugs' => ['Fantasy', 'Horror'],
            ],
            [
                'title' => 'Coffee & Circuit Breakers',
                'slug' => 'coffee-and-circuit-breakers',
                'description' => 'Two unlikely roommates navigate a startup empire, awkward romance, and a city that runs on chance.',
                'cover_image' => 'covers/coffee-and-circuit-breakers.jpg',
                'status' => 'ongoing',
                'published_at' => '2026-03-01 00:00:00',
                'is_featured' => true,
                'seo_title' => 'Coffee & Circuit Breakers',
                'seo_description' => 'A lighthearted comic blending romance, comedy, and modern city life.',
                'genre_slugs' => ['Romance', 'Comedy'],
            ],
        ];

        $comics = [];
        foreach ($comicData as $comicItem) {
            $comic = Comic::updateOrCreate(
                ['slug' => $comicItem['slug']],
                [
                    'title' => $comicItem['title'],
                    'description' => $comicItem['description'],
                    'cover_image' => $comicItem['cover_image'],
                    'status' => $comicItem['status'],
                    'published_at' => $comicItem['published_at'],
                    'is_featured' => $comicItem['is_featured'],
                    'seo_title' => $comicItem['seo_title'],
                    'seo_description' => $comicItem['seo_description'],
                ]
            );

            $genreIds = [];
            foreach ($comicItem['genre_slugs'] as $genreSlug) {
                $genreIds[] = $genres[strtolower($genreSlug)]->id;
            }

            $comic->genres()->syncWithoutDetaching($genreIds);
            $comics[$comicItem['slug']] = $comic;
        }

        foreach ($comics as $comic) {
            foreach ([1, 2, 3] as $chapterNumber) {
                $chapter = Chapter::updateOrCreate(
                    [
                        'comic_id' => $comic->id,
                        'chapter_number' => $chapterNumber,
                    ],
                    [
                        'title' => "Chapter {$chapterNumber}",
                        'slug' => "chapter-{$chapterNumber}",
                        'sort_order' => $chapterNumber,
                        'is_published' => true,
                        'published_at' => now()->subDays(30 - $chapterNumber),
                    ]
                );

                for ($pageNumber = 1; $pageNumber <= 4; $pageNumber++) {
                    Page::updateOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'page_number' => $pageNumber,
                        ],
                        [
                            'title' => "Page {$pageNumber}",
                            'image_path' => "pages/{$comic->slug}/chapter-{$chapterNumber}/page-" . str_pad((string) $pageNumber, 3, '0', STR_PAD_LEFT) . '.jpg',
                        ]
                    );
                }
            }
        }

        foreach ($comics as $comic) {
            Bookmark::updateOrCreate(
                [
                    'user_id' => $reader->id,
                    'comic_id' => $comic->id,
                ],
                []
            );

            $chapter = $comic->chapters()->orderBy('chapter_number')->first();

            ReadingHistory::updateOrCreate(
                [
                    'user_id' => $reader->id,
                    'comic_id' => $comic->id,
                ],
                [
                    'chapter_id' => $chapter?->id,
                    'page_number' => 2,
                    'last_read_at' => now()->subDays(1),
                ]
            );

            Comment::firstOrCreate(
                [
                    'user_id' => $reader->id,
                    'comic_id' => $comic->id,
                    'body' => 'This comic has excellent pacing and strong worldbuilding.',
                ],
                ['is_approved' => true]
            );

            Rating::updateOrCreate(
                [
                    'user_id' => $reader->id,
                    'comic_id' => $comic->id,
                ],
                ['score' => 5]
            );
        }
    }
}
