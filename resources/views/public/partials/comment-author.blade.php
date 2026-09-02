@php
    $authorInitials = collect(preg_split('/\s+/', trim($author->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $hasAuthorPhoto = filled($author->avatar_path) && Storage::disk('public')->exists($author->avatar_path);
@endphp

<div class="comment-author-profile">
    @if ($hasAuthorPhoto)
        <img
            src="{{ Storage::disk('public')->url($author->avatar_path) }}"
            alt="{{ $author->name }} profile photo"
            class="comment-avatar comment-avatar-photo"
            loading="lazy"
            decoding="async"
        >
    @else
        <span class="comment-avatar comment-avatar-fallback" aria-hidden="true">{{ $authorInitials ?: 'ZY' }}</span>
    @endif

    <div class="comment-author-details">
        <div class="comment-author-name">
            <strong>{{ $author->name }}</strong>
            <span class="comment-author-role">{{ $author->role === 'admin' ? 'Admin' : 'Member' }}</span>
        </div>
        <time datetime="{{ $postedAt->toIso8601String() }}" title="{{ $postedAt->format('M d, Y · H:i') }}">
            {{ $postedAt->diffForHumans() }}
        </time>
    </div>
</div>
