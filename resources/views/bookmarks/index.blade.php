@extends('layouts.app')

@section('title', 'My Bookmarks')

@section('content')
    <section class="page-header">
        <div class="container">
            <a href="{{ route('comics') }}" class="back-link">← Back to comics</a>
            <h1>My Bookmarks</h1>
            <p class="meta">Your saved comics.</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if (session('success'))
                <div class="form-success" role="status">{{ session('success') }}</div>
            @endif

            @if ($bookmarks->isEmpty())
                <div class="empty-state bookmark-empty-state">
                    <p>You have no bookmarks yet.</p>
                </div>
            @else
                <div class="card-grid four-up">
                    @foreach ($bookmarks as $bookmark)
                        @php $comic = $bookmark->comic; @endphp

                        @if ($comic)
                            <article class="comic-card">
                                <a href="{{ route('comic.detail', $comic) }}" class="comic-card-link">
                                    <div class="comic-cover-wrap">
                                        <img src="{{ $comic->cover_image && Storage::disk('public')->exists($comic->cover_image) ? Storage::disk('public')->url($comic->cover_image) : asset('images/media-placeholder.svg') }}" alt="{{ $comic->cover_image && Storage::disk('public')->exists($comic->cover_image) ? $comic->title . ' cover' : $comic->title . ' cover unavailable' }}" class="comic-cover">
                                    </div>
                                    <div class="comic-body">
                                        <div class="meta-row">
                                            <span class="badge">{{ ucfirst($comic->status) }}</span>
                                            <span>{{ $comic->published_at ? $comic->published_at->format('M d, Y') : 'Unpublished' }}</span>
                                        </div>
                                        <h3>{{ $comic->title }}</h3>
                                    </div>
                                </a>

                                <div class="comic-body bookmark-card-actions">
                                    <form method="POST" action="{{ route('bookmarks.destroy', $comic) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-block">Remove Bookmark</button>
                                    </form>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
