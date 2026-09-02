@extends('layouts.app')

@section('title', isset($selectedGenre) ? $selectedGenre->name . ' | zYx comic' : 'Genres | zYx comic')

@section('content')
    <section class="page-header">
        <div class="container">
            <p class="eyebrow">Browse the archive</p>
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
                    <div class="card-grid four-up">
                        @foreach ($genreComics as $comic)
                            @include('public.partials.comic-card', ['comic' => $comic, 'showGenres' => false])
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
