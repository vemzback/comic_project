@extends('layouts.app')

@section('title', $chapter->title . ' - ' . $comic->title . ' | Comic Project')

@section('content')
    <section class="page-header">
        <div class="container">
            <a href="{{ route('comic.detail', $comic) }}" class="back-link">← Back to {{ $comic->title }}</a>
            <h1>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</h1>
            <p class="meta">{{ $comic->title }} • Chapter {{ $chapter->chapter_number }}</p>
        </div>
    </section>

    <section class="section-block">
        <div class="container reader-section">
            @if ($pages->isEmpty())
                <div class="empty-state reader-empty">
                    <p>This chapter doesn't have any pages yet.</p>
                    <a href="{{ route('comic.detail', $comic) }}" class="btn btn-secondary">Back to comic</a>
                </div>
            @else
                <div class="reader-pages">
                    @foreach ($pages as $page)
                        <div class="reader-page">
                            <figure class="page-figure">
                                  <img src="{{ Storage::disk('public')->exists($page->image_path) ? Storage::disk('public')->url($page->image_path) : asset('images/media-placeholder.svg') }}"
                                      alt="{{ Storage::disk('public')->exists($page->image_path) ? ($page->title ?: 'Page ' . $page->page_number) : ($page->title ?: 'Page ' . $page->page_number) . ' image unavailable' }}"
                                     class="page-image">
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

    <section class="section-block reader-navigation-section">
        <div class="container">
            <div class="reader-navigation">
                @if ($previousChapter)
                    <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $previousChapter]) }}" class="nav-btn prev-btn">
                        <span class="nav-arrow">←</span>
                        <div>
                            <span class="nav-label">Previous</span>
                            <span class="nav-title">Chapter {{ $previousChapter->chapter_number }}</span>
                        </div>
                    </a>
                @else
                    <div class="nav-btn prev-btn disabled">
                        <span class="nav-label">No previous chapter</span>
                    </div>
                @endif

                <a href="{{ route('comic.detail', $comic) }}" class="nav-btn back-to-comic">
                    <div>
                        <span class="nav-label">Back to</span>
                        <span class="nav-title">{{ $comic->title }}</span>
                    </div>
                </a>

                @if ($nextChapter)
                    <a href="{{ route('chapter.reader', ['comic' => $comic, 'chapter' => $nextChapter]) }}" class="nav-btn next-btn">
                        <div>
                            <span class="nav-label">Next</span>
                            <span class="nav-title">Chapter {{ $nextChapter->chapter_number }}</span>
                        </div>
                        <span class="nav-arrow">→</span>
                    </a>
                @else
                    <div class="nav-btn next-btn disabled">
                        <span class="nav-label">No next chapter</span>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
