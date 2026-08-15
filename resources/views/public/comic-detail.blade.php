@extends('layouts.app')

@section('title', $comic->title . ' | Comic Project')

@section('content')
    <section class="page-header">
        <div class="container">
            <a href="{{ route('comics') }}" class="back-link">← Back to comics</a>
            <h1>{{ $comic->title }}</h1>
            @if ($comic->published_at)
                <p class="meta">Published {{ $comic->published_at->format('M d, Y') }}</p>
            @endif
        </div>
    </section>

    <section class="section-block">
        <div class="container comic-detail-grid">
            <div class="comic-cover-section">
                <div class="comic-cover-detail">
                    <img src="{{ $comic->cover_image ? Storage::disk('public')->url($comic->cover_image) : 'https://placehold.co/600x900/1f2937/ffffff?text=' . urlencode($comic->title) }}" 
                         alt="{{ $comic->title }} cover" 
                         class="comic-cover-large">
                </div>
                <div class="comic-meta">
                    <div class="meta-item">
                        <span class="label">Status</span>
                        <span class="badge">{{ ucfirst($comic->status) }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="label">Chapters</span>
                        <span>{{ $publishedChaptersCount }} published</span>
                    </div>

                    @auth
                        @php
                            $isBookmarked = auth()->user()->bookmarks()->where('comic_id', $comic->id)->exists();
                        @endphp

                        <div class="meta-item">
                            @if ($isBookmarked)
                                <form method="POST" action="{{ route('bookmarks.destroy', $comic) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Remove Bookmark</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('bookmarks.store', $comic) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Bookmark</button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="meta-item">
                            <a href="{{ route('login') }}" class="btn btn-ghost">Login to bookmark</a>
                        </div>
                    @endauth

                    @if ($comic->genres->isNotEmpty())
                        <div class="meta-item">
                            <span class="label">Genres</span>
                            <div class="tag-list">
                                @foreach ($comic->genres as $genre)
                                    <a href="{{ route('genres.show', $genre) }}" class="tag">{{ $genre->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="comic-info-section">
                <div class="comic-description">
                    <h2>About this comic</h2>
                    @if ($comic->description)
                        <p>{{ $comic->description }}</p>
                    @else
                        <p>No description available.</p>
                    @endif
                </div>

                @if ($comic->chapters->isNotEmpty())
                    <div class="chapters-section">
                        <h2>Chapters</h2>
                        <div class="chapter-list">
                            @foreach ($comic->chapters as $chapter)
                                <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]) }}" class="chapter-item">
                                    <div class="chapter-info">
                                        <span class="chapter-number">Chapter {{ $chapter->chapter_number }}</span>
                                        <span class="chapter-title">{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</span>
                                    </div>
                                    <div class="chapter-meta">
                                        @if ($chapter->published_at)
                                            <span class="publish-date">{{ $chapter->published_at->format('M d, Y') }}</span>
                                        @endif
                                        <span class="arrow">→</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <p>No chapters published yet.</p>
                    </div>
                @endif

                <div class="comments-section" style="margin-top: 2rem;">
                    <h2>Comments</h2>

                    @auth
                        <form method="POST" action="{{ route('comments.store', $comic) }}" style="margin-bottom: 1.5rem;">
                            <div style="display:none;"><?php echo '@csrf'; ?></div>
                            @csrf
                            <div class="form-group">
                                <label for="body">Leave a comment</label>
                                <textarea id="body" name="body" rows="4" class="form-control" required>{{ old('body') }}</textarea>
                            </div>
                            @error('body')
                                <div class="alert alert-danger" style="margin-top: .5rem;">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-primary" style="margin-top: .75rem;">Post Comment</button>
                        </form>
                    @else
                        <p><a href="{{ route('login') }}">Login</a> to join the discussion.</p>
                    @endauth

                    @if ($comic->comments->isEmpty())
                        <div class="empty-state">
                            <p>No comments</p>
                        </div>
                    @else
                        @foreach ($comic->comments as $comment)
                            <div class="comment-item" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: #fff;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: .5rem;">
                                    <strong>{{ $comment->user->name }}</strong>
                                    <small>{{ $comment->created_at->format('M d, Y') }}</small>
                                </div>
                                <p style="margin: 0; white-space: pre-line;">{{ $comment->body }}</p>

                                @auth
                                    @if ($comment->user_id === auth()->id())
                                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" style="margin-top: .75rem;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
