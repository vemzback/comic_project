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
                <div class="alert success" style="margin-bottom:1.5rem; background:#ecfdf5; border:1px solid #a7f3d0; color:#166534; padding:0.75rem 1rem; border-radius:12px;">
                    {{ session('success') }}
                </div>
            @endif

            @if ($bookmarks->isEmpty())
                <div class="empty-state" style="background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:2rem; text-align:center;">
                    <p style="margin:0; font-size:1.05rem; color:#4b5563;">You have no bookmarks yet.</p>
                </div>
            @else
                <div class="card-grid four-up">
                    @foreach ($bookmarks as $bookmark)
                        @php $comic = $bookmark->comic; @endphp

                        @if ($comic)
                            <article class="comic-card">
                                <a href="{{ route('comic.detail', $comic) }}" class="comic-card-link">
                                    <div class="comic-cover-wrap">
                                        <img src="{{ $comic->cover_image ? Storage::disk('public')->url($comic->cover_image) : 'https://placehold.co/600x900/1f2937/ffffff?text=' . urlencode($comic->title) }}" alt="{{ $comic->title }} cover" class="comic-cover">
                                    </div>
                                    <div class="comic-body">
                                        <div class="meta-row">
                                            <span class="badge">{{ ucfirst($comic->status) }}</span>
                                            <span>{{ $comic->published_at ? $comic->published_at->format('M d, Y') : 'Unpublished' }}</span>
                                        </div>
                                        <h3>{{ $comic->title }}</h3>
                                    </div>
                                </a>

                                <div class="comic-body" style="padding-top:0;">
                                    <form method="POST" action="{{ route('bookmarks.destroy', $comic) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="width:100%;">Remove Bookmark</button>
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
