@extends('layouts.app')

@section('title', isset($selectedGenre) ? $selectedGenre->name . ' | Comic Project' : 'Genres | Comic Project')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1>{{ isset($selectedGenre) ? $selectedGenre->name : 'Genres' }}</h1>
            <p>{{ isset($selectedGenre) ? 'Browse comics in this genre.' : 'Explore comics by genre.' }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if (isset($selectedGenre))
                <div class="section-heading">
                    <h2>{{ $selectedGenre->name }}</h2>
                    <a href="{{ route('genres') }}">Back to all genres</a>
                </div>

                @if ($genreComics->isEmpty())
                    <p class="empty-state">No comics published in this genre yet.</p>
                @else
                    <div class="card-grid three-up">
                        @foreach ($genreComics as $comic)
                            <a href="{{ route('comic.detail', $comic) }}" class="comic-card comic-card-link">
                                <div class="comic-cover-wrap">
                                    <img src="{{ $comic->cover_image ? Storage::disk('public')->url($comic->cover_image) : 'https://placehold.co/600x900/1f2937/ffffff?text=' . urlencode($comic->title) }}" alt="{{ $comic->title }} cover" class="comic-cover">
                                </div>
                                <div class="comic-body">
                                    <div class="meta-row">
                                        <span class="badge">{{ ucfirst($comic->status) }}</span>
                                        @if ($comic->published_at)
                                            <span>{{ $comic->published_at->format('M d, Y') }}</span>
                                        @endif
                                    </div>
                                    <h3>{{ $comic->title }}</h3>
                                    <p>{{ Str::limit($comic->description ?? '', 130) }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif

            @if (! isset($selectedGenre))
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
            @endif
        </div>
    </section>
@endsection
