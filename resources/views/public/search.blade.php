@extends('layouts.app')

@section('title', 'Search | zYx comic')

@section('content')
    <section class="page-header">
        <div class="container">
            <p class="eyebrow">Search the archive</p>
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
                @if ($topComics->isNotEmpty())
                    <div class="search-top-heading">
                        <div>
                            <p class="eyebrow">Top rated</p>
                            <h2>Start with these comics</h2>
                        </div>
                        <p>Or enter a title, keyword, or story above.</p>
                    </div>

                    <div class="search-top-grid" data-top-comics>
                        @foreach ($topComics as $topComic)
                            @php($topCoverExists = $topComic->cover_image && Storage::disk('public')->exists($topComic->cover_image))
                            <a href="{{ route('comic.detail', $topComic) }}" class="search-top-card">
                                <figure>
                                    <span class="search-top-rank">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <img
                                        src="{{ $topCoverExists ? Storage::disk('public')->url($topComic->cover_image) : asset('images/media-placeholder.svg') }}"
                                        alt="{{ $topCoverExists ? $topComic->title . ' cover' : $topComic->title . ' cover unavailable' }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </figure>
                                <div class="search-top-copy">
                                    <h3>{{ $topComic->title }}</h3>
                                    @include('public.partials.comic-rating', ['comic' => $topComic])
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="empty-state">Enter a search term to find comics.</p>
                @endif
            @elseif ($results->isEmpty())
                <p class="empty-state">No comics matched your search.</p>
            @else
                <div class="card-grid four-up">
                    @foreach ($results as $comic)
                        @include('public.partials.comic-card', ['comic' => $comic])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
