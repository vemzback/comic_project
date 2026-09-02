@extends('layouts.app')

@section('title', $chapter->title . ' - ' . $comic->title . ' | zYx comic')

@section('content')
    @php
        $chapterLabel = 'Chapter ' . $chapter->chapter_number;
        $chapterTitle = $chapter->title ?: $chapterLabel;
        $initialPagePosition = $pages->search(fn ($page) => $page->page_number === $currentPageNumber);
        $initialPagePosition = $initialPagePosition === false ? 1 : $initialPagePosition + 1;
        $canSaveProgress = auth()->check() && auth()->user()->hasVerifiedEmail();
    @endphp

    <section class="reader-header">
        <div class="container">
            <a href="{{ route('comic.detail', $comic) }}" class="reader-back-link">← Back to {{ $comic->title }}</a>
            <p class="eyebrow">{{ $comic->title }}</p>
            <h1>{{ $chapterTitle }}</h1>
            <p class="reader-context">{{ $chapterLabel }}@if ($chapter->published_at) · Published {{ $chapter->published_at->format('M d, Y') }}@endif</p>
        </div>
    </section>

    @if ($pages->isNotEmpty())
        <section class="reader-toolbar" data-reader-toolbar aria-label="Reader controls">
            <div class="container reader-toolbar-inner">
                <div class="reader-toolbar-title">
                    <span>Now reading</span>
                    <strong>{{ $chapterLabel }}</strong>
                </div>

                <div class="reader-progress" aria-label="Reading progress">
                    <div class="reader-progress-track" aria-hidden="true">
                        <span data-reader-progress-bar style="width: {{ ($initialPagePosition / $pages->count()) * 100 }}%"></span>
                    </div>
                    <p><span data-reader-current-position>{{ $initialPagePosition }}</span> / {{ $pages->count() }}</p>
                    <span class="reader-save-status" data-reader-save-status aria-live="polite"></span>
                </div>

                <div class="reader-toolbar-actions">
                    <button type="button" class="reader-tool-button" data-reader-scroll-top>Top ↑</button>
                    <button type="button" class="reader-tool-button" data-reader-focus aria-pressed="false">Focus mode</button>
                </div>
            </div>
        </section>
    @endif

    <section
        class="reader-content"
        data-reader
        data-initial-page="{{ $currentPageNumber }}"
        data-total-pages="{{ $pages->count() }}"
        @if ($canSaveProgress)
            data-progress-url="{{ route('reader.progress', ['comic' => $comic, 'chapter' => $chapter]) }}"
            data-csrf-token="{{ csrf_token() }}"
        @endif
    >
        <div class="container reader-section">
            <div class="reader-navigation reader-navigation-top" aria-label="Chapter navigation">
                @if ($previousChapter)
                    <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $previousChapter]) }}" class="nav-btn prev-btn">
                        <span class="nav-arrow" aria-hidden="true">←</span>
                        <div><span class="nav-label">Previous Chapter</span><span class="nav-title">Chapter {{ $previousChapter->chapter_number }}</span></div>
                    </a>
                @else
                    <div class="nav-btn prev-btn disabled"><span class="nav-label">No previous chapter</span></div>
                @endif

                <a href="{{ route('comic.detail', $comic) }}" class="nav-btn back-to-comic">
                    <div><span class="nav-label">Back to Comic</span><span class="nav-title">{{ $comic->title }}</span></div>
                </a>

                @if ($nextChapter)
                    <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $nextChapter]) }}" class="nav-btn next-btn">
                        <div><span class="nav-label">Next Chapter</span><span class="nav-title">Chapter {{ $nextChapter->chapter_number }}</span></div>
                        <span class="nav-arrow" aria-hidden="true">→</span>
                    </a>
                @else
                    <div class="nav-btn next-btn disabled"><span class="nav-label">No next chapter</span></div>
                @endif
            </div>

            @if ($pages->isEmpty())
                <div class="empty-state reader-empty">
                    <p>This chapter doesn't have any pages available yet.</p>
                    <a href="{{ route('comic.detail', $comic) }}" class="btn btn-secondary">Back to comic</a>
                </div>
            @else
                <div class="reader-pages">
                    @foreach ($pages as $pageIndex => $page)
                        <article
                            id="page-{{ $page->page_number }}"
                            class="reader-page"
                            data-reader-page
                            data-page-number="{{ $page->page_number }}"
                            aria-label="Page {{ $page->page_number }}"
                        >
                            <span class="reader-page-label">Page {{ str_pad((string) $page->page_number, 2, '0', STR_PAD_LEFT) }}</span>
                            <figure class="page-figure">
                                @php($pageExists = Storage::disk('public')->exists($page->image_path))
                                <img src="{{ $pageExists ? Storage::disk('public')->url($page->image_path) : asset('images/media-placeholder.svg') }}"
                                     alt="{{ $comic->title }} - {{ $chapterLabel }} - Page {{ $page->page_number }}{{ $pageExists ? '' : ' image unavailable' }}"
                                     class="page-image"
                                     @if ($pageIndex >= $initialPagePosition) loading="lazy" @endif
                                     decoding="async">
                                @if (! $pageExists)
                                    <p class="page-unavailable">Page {{ $page->page_number }} image unavailable</p>
                                @endif
                                @if ($page->title)
                                    <figcaption>{{ $page->title }}</figcaption>
                                @endif
                            </figure>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="reader-navigation-section">
        <div class="container">
            @if ($pages->isNotEmpty())
                <div class="reader-complete-card">
                    <div>
                        <p class="eyebrow">End of {{ $chapterLabel }}</p>
                        <h2>{{ $nextChapter ? 'Ready for the next chapter?' : 'You are all caught up.' }}</h2>
                    </div>
                    @if ($nextChapter)
                        <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $nextChapter]) }}" class="btn btn-primary">
                            Read Chapter {{ $nextChapter->chapter_number }} →
                        </a>
                    @else
                        <a href="{{ route('comic.detail', $comic) }}" class="btn btn-ghost">Back to Comic</a>
                    @endif
                </div>
            @endif

            <div class="reader-navigation reader-navigation-bottom" aria-label="Chapter navigation">
                @if ($previousChapter)
                    <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $previousChapter]) }}" class="nav-btn prev-btn">
                        <span class="nav-arrow" aria-hidden="true">←</span>
                        <div><span class="nav-label">Previous Chapter</span><span class="nav-title">Chapter {{ $previousChapter->chapter_number }}</span></div>
                    </a>
                @else
                    <div class="nav-btn prev-btn disabled"><span class="nav-label">No previous chapter</span></div>
                @endif

                <a href="{{ route('comic.detail', $comic) }}" class="nav-btn back-to-comic">
                    <div><span class="nav-label">Back to Comic</span><span class="nav-title">{{ $comic->title }}</span></div>
                </a>

                @if ($nextChapter)
                    <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $nextChapter]) }}" class="nav-btn next-btn">
                        <div><span class="nav-label">Next Chapter</span><span class="nav-title">Chapter {{ $nextChapter->chapter_number }}</span></div>
                        <span class="nav-arrow" aria-hidden="true">→</span>
                    </a>
                @else
                    <div class="nav-btn next-btn disabled"><span class="nav-label">No next chapter</span></div>
                @endif
            </div>
        </div>
    </section>
@endsection
