@extends('layouts.app')

@section('title', 'Search | Comic Project')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1>Search comics</h1>
            <form method="GET" action="{{ route('search') }}" class="search-form">
                <label for="q" class="sr-only">Search</label>
                <input id="q" type="search" name="q" value="{{ old('q', $query) }}" placeholder="Search comics...">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if ($query === '')
                <p class="empty-state">Enter a search term to find comics.</p>
            @elseif ($results->isEmpty())
                <p class="empty-state">No comics matched your search.</p>
            @else
                <div class="card-grid three-up">
                    @foreach ($results as $comic)
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
                                <p>{{ Str::limit($comic->description ?? '', 130) }}</p>
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
@endsection
