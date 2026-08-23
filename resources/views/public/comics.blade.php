@extends('layouts.app')

@section('title', 'Comics | Comic Project')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1>Comics</h1>
            <p>Browse the latest stories from our library.</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container">
            @if ($comics->isEmpty())
                <p class="empty-state">No comics published yet.</p>
            @else
                <div class="card-grid three-up">
                    @foreach ($comics as $comic)
                        <a href="{{ route('comic.detail', $comic) }}" class="comic-card comic-card-link">
                            <div class="comic-cover-wrap">
                                <img src="{{ $comic->cover_image && Storage::disk('public')->exists($comic->cover_image) ? Storage::disk('public')->url($comic->cover_image) : asset('images/media-placeholder.svg') }}" alt="{{ $comic->cover_image && Storage::disk('public')->exists($comic->cover_image) ? $comic->title . ' cover' : $comic->title . ' cover unavailable' }}" class="comic-cover">
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

                {{ $comics->links() }}
            @endif
        </div>
    </section>
@endsection
