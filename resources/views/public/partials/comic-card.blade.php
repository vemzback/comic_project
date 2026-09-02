@php
    $coverExists = $comic->cover_image && Storage::disk('public')->exists($comic->cover_image);
    $descriptionLimit = $descriptionLimit ?? 130;
    $showGenres = $showGenres ?? true;
@endphp

<a href="{{ route('comic.detail', $comic) }}" class="comic-card comic-card-link">
    <div class="comic-cover-wrap">
        <img
            src="{{ $coverExists ? Storage::disk('public')->url($comic->cover_image) : asset('images/media-placeholder.svg') }}"
            alt="{{ $coverExists ? $comic->title.' cover' : $comic->title.' cover unavailable' }}"
            class="comic-cover"
            loading="lazy"
            decoding="async"
        >
    </div>
    <div class="comic-body">
        <div class="meta-row">
            <span class="badge">{{ ucfirst($comic->status) }}</span>
            @if ($comic->published_at)
                <span>{{ $comic->published_at->format('M d, Y') }}</span>
            @endif
        </div>
        <h3>{{ $comic->title }}</h3>
        @include('public.partials.comic-rating', ['comic' => $comic])
        <p>{{ Str::limit($comic->description ?? '', $descriptionLimit) }}</p>
        @if ($showGenres && $comic->genres->isNotEmpty())
            <div class="tag-list">
                @foreach ($comic->genres->take(3) as $genre)
                    <span class="tag">{{ $genre->name }}</span>
                @endforeach
            </div>
        @endif
    </div>
</a>
