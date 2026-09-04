@extends('layouts.app')

@section('title', 'Home | zYx comic')

@section('content')
    <section class="hero-section">
        <div class="container catalog-hero">
            @php
                $spotlight = $latestComics->firstWhere('title', 'NailBiter: The Munder Edition')
                    ?? $featuredComics->first();
            @endphp

            <div class="catalog-hero-top">
                <p class="eyebrow">Digital comics / Curated weekly</p>
                <p class="catalog-index">Issue 001 — {{ now()->format('Y') }}</p>
            </div>
            <h1>zYx comic</h1>
            <div class="catalog-hero-bottom">
                <div class="catalog-intro">
                    <p class="lead">Explore original worlds, new chapters, and genre-defining stories in one bold digital collection.</p>
                    <div class="hero-actions">
                        <a href="{{ route('comics') }}" class="btn btn-primary">Explore catalog</a>
                        <a href="{{ route('genres') }}" class="text-link">Browse by genre <span aria-hidden="true">↗</span></a>
                    </div>
                </div>

                @if ($spotlight)
                    <a href="{{ route('comic.detail', $spotlight) }}" class="catalog-spotlight">
                        <img src="{{ $spotlight->cover_image && Storage::disk('public')->exists($spotlight->cover_image) ? Storage::disk('public')->url($spotlight->cover_image) : asset('images/media-placeholder.svg') }}"
                             alt="{{ $spotlight->title }} cover"
                             class="spotlight-cover"
                             decoding="async">
                        <span class="spotlight-copy">
                            <span class="card-label">Featured release</span>
                            <strong>{{ $spotlight->title }}</strong>
                            <span>Read now ↗</span>
                        </span>
                    </a>
                @else
                    <div class="catalog-spotlight catalog-spotlight-empty">
                        <span class="spotlight-copy">
                            <span class="card-label">Featured release</span>
                            <strong>New stories coming soon</strong>
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Editor selections</p>
                    <h2>Featured comics</h2>
                </div>
                <a href="{{ route('comics') }}">View all ↗</a>
            </div>

            @if ($featuredComics->isEmpty())
                <p class="empty-state">No featured comics yet.</p>
            @else
                <div class="card-grid four-up">
                    @foreach ($featuredComics as $comic)
                        @include('public.partials.comic-card', ['comic' => $comic, 'descriptionLimit' => 110])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section-block alt-block">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Fresh from the press</p>
                    <h2>Latest comics</h2>
                </div>
                <a href="{{ route('comics') }}">Explore ↗</a>
            </div>

            @if ($latestComics->isEmpty())
                <p class="empty-state">No latest comics available right now.</p>
            @else
                <div class="card-grid four-up">
                    @foreach ($latestComics as $comic)
                        @include('public.partials.comic-card', ['comic' => $comic, 'descriptionLimit' => 120])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @auth
        <section class="section-block">
            <div class="container">
                <div class="section-heading">
                    <div>
                        @if ($continueReading->isNotEmpty())
                            <p class="eyebrow">Pick up where you left off</p>
                            <h2>Continue reading</h2>
                        @else
                            <p class="eyebrow">Start a new story</p>
                            <h2>Latest chapters</h2>
                        @endif
                    </div>
                    @if ($continueReading->isNotEmpty())
                        <a href="{{ route('history.index') }}">Reading history ↗</a>
                    @endif
                </div>

                @if ($continueReading->isNotEmpty())
                    <div class="chapter-list">
                        @foreach ($continueReading as $history)
                            <a href="{{ route('chapter.reader', ['comic' => $history->comic, 'chapter' => $history->chapter]) }}{{ $history->page_number ? '?page=' . $history->page_number : '' }}" class="chapter-item chapter-item-link">
                                <div>
                                    <p class="chapter-meta">{{ $history->comic?->title ?? 'Unknown comic' }}</p>
                                    <h3>{{ $history->chapter?->title ?: 'Chapter ' . $history->chapter?->chapter_number }}</h3>
                                </div>
                                <div class="chapter-actions">
                                    @if ($history->page_number)
                                        <span>Page {{ $history->page_number }}</span>
                                    @endif
                                    <span>{{ $history->last_read_at?->diffForHumans() }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @elseif ($latestChapters->isNotEmpty())
                    <div class="chapter-list">
                        @foreach ($latestChapters as $chapter)
                            <a href="{{ route('chapter.reader', ['comic' => $chapter->comic, 'chapter' => $chapter]) }}" class="chapter-item chapter-item-link">
                                <div>
                                    <p class="chapter-meta">{{ $chapter->comic?->title ?? 'Unknown comic' }}</p>
                                    <h3>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</h3>
                                </div>
                                <div class="chapter-actions">
                                    <span>Chapter {{ $chapter->chapter_number }}</span>
                                    @if ($chapter->published_at)
                                        <span>{{ $chapter->published_at->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="empty-state">No chapters available yet.</p>
                @endif
            </div>
        </section>
    @endauth

    <section class="section-block alt-block">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Find your world</p>
                    <h2>Genres</h2>
                </div>
                <a href="{{ route('genres') }}">See all ↗</a>
            </div>

            @if ($genres->isEmpty())
                <p class="empty-state">No genres available yet.</p>
            @else
                <div class="genre-grid">
                    @foreach ($genres as $genre)
                        <a href="{{ route('genres.show', $genre) }}" class="genre-card">
                            <span class="genre-name">{{ $genre->name }}</span>
                            <span class="genre-count">{{ $genre->comics_count }} comic(s)</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
