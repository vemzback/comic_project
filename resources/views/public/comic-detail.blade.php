@extends('layouts.app')

@section('title', $comic->title . ' | zYx comic')

@if ($comic->external_provider === 'google_books'
    && filled($comic->external_id)
    && data_get($comic->source_metadata, 'preview.embeddable') !== false)
    @push('head')
        <script src="https://www.google.com/books/jsapi.js"></script>
        <script>
            window.zyxGoogleBooksReady = new Promise(function (resolve) {
                var completed = false;
                var loader = window.google && window.google.books;
                var timeout = window.setTimeout(function () {
                    finish(null);
                }, 15000);

                function finish(books) {
                    if (completed) return;

                    completed = true;
                    window.clearTimeout(timeout);
                    resolve(books);
                }

                if (!loader) {
                    finish(null);
                    return;
                }

                try {
                    loader.load({ language: 'id' });
                    loader.setOnLoadCallback(function () {
                        finish((window.google && window.google.books) || loader);
                    });
                } catch (error) {
                    finish(null);
                }
            });
        </script>
    @endpush
@endif

@section('content')
    @php
        $coverExists = $comic->cover_image && Storage::disk('public')->exists($comic->cover_image);
        $firstChapter = $comic->chapters->first();
        $readingChapter = $continueReadingHistory?->chapter ?: $firstChapter;
        $readingUrl = $readingChapter
            ? route('chapter.reader', array_filter([
                'comic' => $comic,
                'chapter' => $readingChapter,
                'page' => $continueReadingHistory?->page_number,
            ]))
            : null;
        $discussionCount = $comic->comments->sum(fn ($comment) => 1 + $comment->replies->count());
        $isBookmarked = auth()->check()
            ? auth()->user()->bookmarks()->where('comic_id', $comic->id)->exists()
            : false;
        $googleBooksPreview = $comic->external_provider === 'google_books'
            && filled($comic->external_id)
            && data_get($comic->source_metadata, 'preview.embeddable') !== false;
        $googleBooksReaderUrl = data_get($comic->source_metadata, 'preview.web_reader_url') ?: $comic->source_url;
        $googleBooksReaderUrl = is_string($googleBooksReaderUrl) && str_starts_with($googleBooksReaderUrl, 'https://')
            ? $googleBooksReaderUrl
            : null;
    @endphp

    <section class="hybrid-detail-hero">
        <div class="container">
            <div class="hybrid-detail-topline">
                <a href="{{ route('comics') }}" class="back-link">← Back to comics</a>
                <span>Library entry {{ str_pad((string) $comic->id, 3, '0', STR_PAD_LEFT) }}</span>
            </div>

            <div class="hybrid-detail-grid">
                <div class="hybrid-cover-column">
                    <figure class="hybrid-cover-frame">
                        <img
                            src="{{ $coverExists ? Storage::disk('public')->url($comic->cover_image) : asset('images/media-placeholder.svg') }}"
                            alt="{{ $coverExists ? $comic->title . ' cover' : $comic->title . ' cover unavailable' }}"
                            class="comic-cover-large"
                        >
                        <figcaption>
                            <span>zYx edition</span>
                            <span>{{ $comic->status }}</span>
                        </figcaption>
                    </figure>
                </div>

                <div class="hybrid-detail-copy">
                    <p class="eyebrow">Featured library title</p>
                    <h1>{{ $comic->title }}</h1>

                    <div class="hybrid-title-meta" aria-label="Comic overview">
                        <span class="hybrid-status-dot"><i aria-hidden="true"></i>{{ ucfirst($comic->status) }}</span>
                        <span>{{ $publishedChaptersCount }} {{ $publishedChaptersCount === 1 ? 'chapter' : 'chapters' }}</span>
                        <span>{{ $ratingCount }} {{ $ratingCount === 1 ? 'rating' : 'ratings' }}</span>
                    </div>

                    @if ($comic->author || $comic->publisher)
                        <p class="hybrid-creator-line">
                            @if ($comic->author)<span>By {{ $comic->author }}</span>@endif
                            @if ($comic->publisher)<span>{{ $comic->publisher }}</span>@endif
                        </p>
                    @endif

                    <div class="hybrid-description" data-expandable-description>
                        <p class="hybrid-kicker">The story</p>
                        @if ($comic->description)
                            <p id="comic-description-{{ $comic->id }}" class="hybrid-description-text" data-description-text>{{ $comic->description }}</p>
                            <button
                                type="button"
                                class="hybrid-description-toggle"
                                data-description-toggle
                                aria-expanded="false"
                                aria-controls="comic-description-{{ $comic->id }}"
                                hidden
                            >Selengkapnya <span aria-hidden="true">↓</span></button>
                        @else
                            <p>No description available.</p>
                        @endif
                    </div>

                    @if ($comic->genres->isNotEmpty())
                        <div class="hybrid-genres" aria-label="Genres">
                            @foreach ($comic->genres as $genre)
                                <a href="{{ route('genres.show', $genre) }}">{{ $genre->name }}</a>
                            @endforeach
                        </div>
                    @endif

                    <div class="hybrid-primary-actions">
                        @if ($readingUrl)
                            <a href="{{ $readingUrl }}" class="btn btn-primary">
                                {{ $continueReadingHistory ? 'Continue Reading' : 'Start Reading' }}
                            </a>
                        @endif

                        @auth
                            @if ($isBookmarked)
                                <form method="POST" action="{{ route('bookmarks.destroy', $comic) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost">Remove Bookmark</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('bookmarks.store', $comic) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost">Add to Bookmarks</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-ghost">Login to Bookmark</a>
                        @endauth

                        @if ($googleBooksPreview)
                            <button
                                type="button"
                                class="btn btn-ghost"
                                data-google-books-preview-toggle
                                aria-expanded="false"
                                aria-controls="google-books-preview-{{ $comic->id }}"
                            >Preview on Google Books</button>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="hybrid-facts">
                <div>
                    <dt>Format</dt>
                    <dd>Digital comic</dd>
                </div>
                <div>
                    <dt>Published</dt>
                    <dd>{{ $comic->original_published_at?->format('M d, Y') ?? $comic->published_at?->format('M d, Y') ?? 'Not scheduled' }}</dd>
                </div>
                <div>
                    <dt>Chapters</dt>
                    <dd>{{ $publishedChaptersCount }} available</dd>
                </div>
                <div>
                    <dt>Community rating</dt>
                    <dd>{{ $ratingCount > 0 ? number_format((float) $averageRating, 1) . ' / 5' : 'Not rated' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="hybrid-detail-content">
        <div class="container">
            @if ($googleBooksPreview)
                <section
                    id="google-books-preview-{{ $comic->id }}"
                    class="google-books-preview-panel"
                    data-google-books-preview
                    data-volume-id="{{ $comic->external_id }}"
                    aria-labelledby="google-books-preview-heading-{{ $comic->id }}"
                    hidden
                >
                    <div class="google-books-preview-heading">
                        <div>
                            <p class="eyebrow">Official sample</p>
                            <h2 id="google-books-preview-heading-{{ $comic->id }}">Google Books Preview</h2>
                        </div>
                        <button type="button" class="google-books-preview-close" data-google-books-preview-close aria-label="Close Google Books preview">Close ×</button>
                    </div>

                    <p class="google-books-preview-status" data-google-books-preview-status role="status" aria-live="polite">
                        The official preview will load here.
                    </p>
                    <div class="google-books-viewer" data-google-books-viewer aria-label="Google Books embedded preview"></div>

                    <div class="google-books-preview-footer">
                        <p>Preview availability and page limits are controlled by Google Books and the publisher.</p>
                        @if ($googleBooksReaderUrl)
                            <a href="{{ $googleBooksReaderUrl }}" target="_blank" rel="noopener noreferrer">Open on Google Books ↗</a>
                        @endif
                    </div>
                </section>
            @endif

            <div class="hybrid-content-grid">
                <section class="hybrid-panel hybrid-panel-light chapters-section" aria-labelledby="chapters-heading">
                    <div class="hybrid-panel-heading">
                        <div>
                            <p class="eyebrow">Read online</p>
                            <h2 id="chapters-heading">Chapters</h2>
                        </div>
                        <span>{{ str_pad((string) $publishedChaptersCount, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    @if ($comic->chapters->isNotEmpty())
                        <div class="chapter-list">
                            @foreach ($comic->chapters as $chapter)
                                <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $chapter]) }}" class="chapter-item">
                                    <div class="chapter-info">
                                        <span class="chapter-number">Chapter {{ str_pad((string) $chapter->chapter_number, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="chapter-title">{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</span>
                                    </div>
                                    <div class="chapter-meta">
                                        @if ($chapter->published_at)
                                            <span class="publish-date">{{ $chapter->published_at->diffForHumans() }}</span>
                                        @endif
                                        <span class="arrow" aria-hidden="true">↗</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="hybrid-empty-state">
                            <p>No chapters published yet.</p>
                        </div>
                    @endif
                </section>

                <aside class="hybrid-panel hybrid-rating-card rating-section" aria-labelledby="rating-heading">
                    <div class="hybrid-panel-heading">
                        <div>
                            <p class="eyebrow">Community score</p>
                            <h2 id="rating-heading">Rating</h2>
                        </div>
                        <span aria-hidden="true">★</span>
                    </div>

                    <p class="rating-summary" data-rating-summary>
                        <strong data-rating-score @if ($ratingCount === 0) hidden @endif>
                            <span data-rating-average>{{ number_format((float) $averageRating, 1) }}</span>/5
                        </strong>
                        <span data-rating-copy @if ($ratingCount === 0) hidden @endif>
                            from <span data-rating-count>{{ $ratingCount }}</span>
                            <span data-rating-label>{{ $ratingCount === 1 ? 'rating' : 'ratings' }}</span>
                        </span>
                        <span data-rating-empty @if ($ratingCount > 0) hidden @endif>No ratings yet.</span>
                    </p>
                    <p class="rating-live-status" data-rating-status role="status" aria-live="polite"></p>

                    @auth
                        @php($selectedRating = old('score', optional($userRating)->score))
                        <form method="POST" action="{{ route('ratings.store', $comic) }}" class="interaction-form rating-form" data-rating-form>
                            @csrf
                            <fieldset class="star-rating-fieldset">
                                <legend>Your rating</legend>
                                <div class="star-rating">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <input id="rating-{{ $i }}" type="radio" name="score" value="{{ $i }}" @checked((int) $selectedRating === $i) required>
                                        <label for="rating-{{ $i }}" title="{{ $i }} out of 5">
                                            <span aria-hidden="true">★</span>
                                            <span class="sr-only">{{ $i }} out of 5</span>
                                        </label>
                                    @endfor
                                </div>
                            </fieldset>
                            @error('score')
                                <div class="alert alert-danger interaction-error">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-primary interaction-submit">
                                {{ $userRating ? 'Update Rating' : 'Save Rating' }}
                            </button>
                        </form>
                    @else
                        <p><a href="{{ route('login') }}">Login</a> to rate this comic.</p>
                    @endauth
                </aside>
            </div>

            <section class="hybrid-panel hybrid-reviews comments-section" aria-labelledby="comments-heading">
                <div class="hybrid-panel-heading hybrid-reviews-heading">
                    <div>
                        <p class="eyebrow">Reader conversation</p>
                        <h2 id="comments-heading">Comments</h2>
                    </div>
                    <span>{{ $discussionCount }} {{ $discussionCount === 1 ? 'message' : 'messages' }}</span>
                </div>

                @auth
                    <form method="POST" action="{{ route('comments.store', $comic) }}" class="comment-form hybrid-comment-composer" data-comment-action>
                        @csrf
                        <div class="form-group">
                            <label for="body">Share your thoughts</label>
                            <textarea id="body" name="body" rows="4" class="form-control" placeholder="What did you think about this comic?" required>{{ old('parent_id') ? '' : old('body') }}</textarea>
                        </div>
                        @if (! old('parent_id'))
                            @error('body')
                                <div class="alert alert-danger interaction-error">{{ $message }}</div>
                            @enderror
                        @endif
                        <button type="submit" class="btn btn-primary interaction-submit">Post Comment</button>
                    </form>
                @else
                    <p class="hybrid-login-note"><a href="{{ route('login') }}">Login</a> to join the discussion.</p>
                @endauth

                <p class="comment-live-status" data-comment-status role="status" aria-live="polite"></p>
                <div class="comment-list" data-comment-list data-feed-url="{{ route('comments.feed', $comic) }}">
                    @include('public.partials.comment-list', ['comments' => $comic->comments, 'comic' => $comic])
                </div>
            </section>
        </div>
    </section>
@endsection
