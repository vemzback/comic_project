@extends('layouts.app')

@section('title', $chapter->title . ' - ' . $comic->title . ' | Comic Project')

@section('content')
    @php
        $chapterLabel = 'Chapter ' . $chapter->chapter_number;
        $chapterTitle = $chapter->title ?: $chapterLabel;
    @endphp

    <section class="reader-header">
        <div class="container">
            <a href="{{ route('comic.detail', $comic) }}" class="reader-back-link">← Back to {{ $comic->title }}</a>
            <p class="eyebrow">{{ $comic->title }}</p>
            <h1>{{ $chapterTitle }}</h1>
            <p class="reader-context">{{ $chapterLabel }}@if ($chapter->published_at) · Published {{ $chapter->published_at->format('M d, Y') }}@endif</p>
        </div>
    </section>

    <section class="reader-content">
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
                        <div class="reader-page">
                            <figure class="page-figure">
                                @php($pageExists = Storage::disk('public')->exists($page->image_path))
                                <img src="{{ $pageExists ? Storage::disk('public')->url($page->image_path) : asset('images/media-placeholder.svg') }}"
                                     alt="{{ $comic->title }} - {{ $chapterLabel }} - Page {{ $page->page_number }}{{ $pageExists ? '' : ' image unavailable' }}"
                                     class="page-image"
                                     @if ($pageIndex > 0) loading="lazy" @endif
                                     decoding="async">
                                @if (! $pageExists)
                                    <p class="page-unavailable">Page {{ $page->page_number }} image unavailable</p>
                                @endif
                                @if ($page->title)
                                    <figcaption>{{ $page->title }}</figcaption>
                                @endif
                            </figure>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="reader-navigation-section">
        <div class="container">
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
