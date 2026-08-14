@extends('layouts.app')

@section('title', 'Home | Comic Project')

@section('content')
    <section class="hero-section">
        <div class="container hero-grid">
            <div>
                <p class="eyebrow">Your next favorite series</p>
                <h1>Read comics that keep you turning pages.</h1>
                <p class="lead">Discover featured stories, fresh releases, and genre favorites all in one place.</p>
                <div class="hero-actions">
                    <a href="{{ route('comics') }}" class="btn btn-primary">Browse comics</a>
                    <a href="{{ route('genres') }}" class="btn btn-secondary">Browse genres</a>
                </div>
            </div>
            <div class="hero-panel">
                <div class="hero-card">
                    <p class="card-label">Featured</p>
                    @if ($featuredComics->isNotEmpty())
                        <h2>{{ $featuredComics->first()->title }}</h2>
                        <p>{{ Str::limit($featuredComics->first()->description ?? 'Explore a full comic experience with thrilling chapters and rich worlds.', 150) }}</p>
                    @else
                        <h2>New stories coming soon</h2>
                        <p>Featured comics will appear here once publishing starts.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="section-heading">
                <h2>Featured comics</h2>
                <a href="{{ route('comics') }}">View all</a>
            </div>

            @if ($featuredComics->isEmpty())
                <p class="empty-state">No featured comics yet.</p>
            @else
                <div class="card-grid four-up">
                    @foreach ($featuredComics as $comic)
                        <a href="{{ route('comic.detail', $comic) }}" class="comic-card comic-card-link">
                            <div class="comic-cover-wrap">
                                <img src="{{ $comic->cover_image ?: 'https://placehold.co/600x900/1f2937/ffffff?text=' . urlencode($comic->title) }}" alt="{{ $comic->title }} cover" class="comic-cover">
                            </div>
                            <div class="comic-body">
                                <div class="meta-row">
                                    <span class="badge">{{ ucfirst($comic->status) }}</span>
                                    @if ($comic->published_at)
                                        <span>{{ $comic->published_at->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                <h3>{{ $comic->title }}</h3>
                                <p>{{ Str::limit($comic->description ?? '', 110) }}</p>
                                @if ($comic->genres->isNotEmpty())
                                    <div class="tag-list">
                                        @foreach ($comic->genres->take(3) as $genre)
                                            <span class="tag">{{ $genre->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section-block alt-block">
        <div class="container">
            <div class="section-heading">
                <h2>Latest comics</h2>
                <a href="{{ route('comics') }}">Explore</a>
            </div>

            @if ($latestComics->isEmpty())
                <p class="empty-state">No latest comics available right now.</p>
            @else
                <div class="card-grid three-up">
                    @foreach ($latestComics as $comic)
                        <a href="{{ route('comic.detail', $comic) }}" class="comic-card comic-card-link">
                            <div class="comic-cover-wrap">
                                <img src="{{ $comic->cover_image ?: 'https://placehold.co/600x900/374151/ffffff?text=' . urlencode($comic->title) }}" alt="{{ $comic->title }} cover" class="comic-cover">
                            </div>
                            <div class="comic-body">
                                <div class="meta-row">
                                    <span class="badge">{{ ucfirst($comic->status) }}</span>
                                    @if ($comic->published_at)
                                        <span>{{ $comic->published_at->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                <h3>{{ $comic->title }}</h3>
                                <p>{{ Str::limit($comic->description ?? '', 120) }}</p>
                                @if ($comic->genres->isNotEmpty())
                                    <div class="tag-list">
                                        @foreach ($comic->genres->take(3) as $genre)
                                            <span class="tag">{{ $genre->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            <div class="section-heading">
                <h2>Latest chapters</h2>
            </div>

            @if ($latestChapters->isEmpty())
                <p class="empty-state">No latest chapters available.</p>
            @else
                <div class="chapter-list">
                    @foreach ($latestChapters as $chapter)
                        <a href="{{ route('chapter.reader', ['comic' => $chapter->comic, 'chapter' => $chapter]) }}" class="chapter-item chapter-item-link">
                            <div>
                                <p class="chapter-meta">
                                    {{ $chapter->comic?->title ?? 'Unknown comic' }}
                                </p>
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
            @endif
        </div>
    </section>

    <section class="section-block alt-block">
        <div class="container">
            <div class="section-heading">
                <h2>Genres</h2>
                <a href="{{ route('genres') }}">See all</a>
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
