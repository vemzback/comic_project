@extends('layouts.app')

@section('title', 'Home | zYx comic')

@section('content')
    <section class="hero-section home-fan-hero">
        <div class="container">
            @php
                $heroComics = $featuredComics
                    ->concat($latestComics)
                    ->unique('id')
                    ->take(7)
                    ->values();
            @endphp

            @if ($heroComics->isNotEmpty())
                <div class="home-fan-carousel" data-home-fan tabindex="0" aria-label="Featured comic carousel">
                    <div class="home-fan-stage" data-home-fan-stage>
                        @foreach ($heroComics as $comic)
                            <a href="{{ route('comic.detail', $comic) }}"
                               class="home-fan-card"
                               data-home-fan-card
                               data-title="{{ $comic->title }}"
                               aria-label="View {{ $comic->title }}">
                                <img src="{{ $comic->cover_image && Storage::disk('public')->exists($comic->cover_image) ? Storage::disk('public')->url($comic->cover_image) : asset('images/media-placeholder.svg') }}"
                                     alt="{{ $comic->title }} cover"
                                     decoding="async"
                                     @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                <span class="home-fan-card-edge" aria-hidden="true"></span>
                            </a>
                        @endforeach
                    </div>

                    <div class="home-fan-controls">
                        <button type="button" class="home-fan-arrow" data-home-fan-prev aria-label="Previous comic">
                            <span aria-hidden="true">←</span>
                        </button>
                        <p class="home-fan-current" aria-live="polite">
                            <span data-home-fan-current>01</span>
                            <strong data-home-fan-title>{{ $heroComics->first()->title }}</strong>
                            <span>/ {{ str_pad((string) $heroComics->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        </p>
                        <button type="button" class="home-fan-arrow" data-home-fan-next aria-label="Next comic">
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="home-fan-empty">
                    <p>No published comics are available yet.</p>
                    <a href="{{ route('comics') }}" class="btn btn-primary">Explore catalog</a>
                </div>
            @endif
        </div>
    </section>

    <section class="section-block alt-block">
        <div class="container">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Fresh from the press</p>
                    <h2>Latest comics</h2>
                </div>
                <a href="{{ route('comics') }}">Explore ↗</a>
            </div>

            @if ($latestComics->isEmpty())
                <p class="empty-state">No latest comics available right now.</p>
            @else
                <div class="card-grid four-up">
                    @foreach ($latestComics as $comic)
                        @include('public.partials.comic-card', ['comic' => $comic, 'descriptionLimit' => 120])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @auth
        <section class="section-block">
            <div class="container">
                <div class="section-heading">
                    <div>
                        @if ($continueReading->isNotEmpty())
                            <p class="eyebrow">Pick up where you left off</p>
                            <h2>Continue reading</h2>
                        @else
                            <p class="eyebrow">Start a new story</p>
                            <h2>Latest chapters</h2>
                        @endif
                    </div>
                    @if ($continueReading->isNotEmpty())
                        <a href="{{ route('history.index') }}">Reading history ↗</a>
                    @endif
                </div>

                @if ($continueReading->isNotEmpty())
                    <div class="chapter-list">
                        @foreach ($continueReading as $history)
                            <a href="{{ route('chapter.reader', ['comic' => $history->comic, 'chapter' => $history->chapter]) }}{{ $history->page_number ? '?page=' . $history->page_number : '' }}" class="chapter-item chapter-item-link">
                                <div>
                                    <p class="chapter-meta">{{ $history->comic?->title ?? 'Unknown comic' }}</p>
                                    <h3>{{ $history->chapter?->title ?: 'Chapter ' . $history->chapter?->chapter_number }}</h3>
                                </div>
                                <div class="chapter-actions">
                                    @if ($history->page_number)
                                        <span>Page {{ $history->page_number }}</span>
                                    @endif
                                    <span>{{ $history->last_read_at?->diffForHumans() }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @elseif ($latestChapters->isNotEmpty())
                    <div class="chapter-list">
                        @foreach ($latestChapters as $chapter)
                            <a href="{{ route('chapter.reader', ['comic' => $chapter->comic, 'chapter' => $chapter]) }}" class="chapter-item chapter-item-link">
                                <div>
                                    <p class="chapter-meta">{{ $chapter->comic?->title ?? 'Unknown comic' }}</p>
                                    <h3>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</h3>
                                </div>
                                <div class="chapter-actions">
                                    <span>Chapter {{ $chapter->chapter_number }}</span>
                                    @if ($chapter->published_at)
                                        <span>{{ $chapter->published_at->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="empty-state">No chapters available yet.</p>
                @endif
            </div>
        </section>
    @endauth

@endsection
