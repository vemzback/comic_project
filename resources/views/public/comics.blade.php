@extends('layouts.app')

@section('title', 'Comics | zYx comic')

@section('content')
    @php($catalogTotal = method_exists($comics, 'total') ? $comics->total() : $comics->count())

    @if ($heroComics->isNotEmpty())
        <section class="catalog-showcase" data-catalog-slider aria-label="Featured comic carousel" aria-roledescription="carousel">
            <div class="container">
                <div class="catalog-showcase-topline">
                    <div>
                        <p class="eyebrow">Catalog selection</p>
                        <span>{{ $catalogTotal }} published {{ $catalogTotal === 1 ? 'title' : 'titles' }}</span>
                    </div>
                    <p>Featured and recently published stories</p>
                </div>

                <div class="catalog-slider-viewport">
                    <div class="catalog-slider-track">
                        @foreach ($heroComics as $heroComic)
                            @php($heroCoverExists = $heroComic->cover_image && Storage::disk('public')->exists($heroComic->cover_image))
                            <article
                                class="catalog-slide"
                                data-catalog-slide
                                data-slide-index="{{ $loop->index }}"
                                aria-label="Slide {{ $loop->iteration }} of {{ $heroComics->count() }}"
                                aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                                @if ($loop->first) data-active @else inert @endif
                            >
                                <div class="catalog-slide-copy">
                                    <p class="catalog-slide-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) $heroComics->count(), 2, '0', STR_PAD_LEFT) }}</p>
                                    <p class="eyebrow">{{ $heroComic->is_featured ? 'Featured release' : 'New in the library' }}</p>
                                    <h1>{{ $heroComic->title }}</h1>

                                    <div class="catalog-slide-meta">
                                        <span>{{ ucfirst($heroComic->status) }}</span>
                                        @if ($heroComic->ratings_count > 0)
                                            <span><b aria-hidden="true">★</b> {{ number_format((float) $heroComic->ratings_avg_score, 1) }} ({{ $heroComic->ratings_count }})</span>
                                        @else
                                            <span>Not rated</span>
                                        @endif
                                    </div>

                                    <p class="catalog-slide-description">{{ Str::limit($heroComic->description ?: 'Discover this story in the zYx comic library.', 180) }}</p>

                                    @if ($heroComic->genres->isNotEmpty())
                                        <div class="catalog-slide-genres" aria-label="Genres">
                                            @foreach ($heroComic->genres->take(3) as $genre)
                                                <span>{{ $genre->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <a href="{{ route('comic.detail', $heroComic) }}" class="btn btn-primary">Explore Comic</a>
                                </div>

                                <a href="{{ route('comic.detail', $heroComic) }}" class="catalog-slide-art" aria-label="Open {{ $heroComic->title }}">
                                    <span class="catalog-slide-art-back" aria-hidden="true"></span>
                                    <img
                                        src="{{ $heroCoverExists ? Storage::disk('public')->url($heroComic->cover_image) : asset('images/media-placeholder.svg') }}"
                                        alt="{{ $heroCoverExists ? $heroComic->title . ' cover' : $heroComic->title . ' cover unavailable' }}"
                                        @if (! $loop->first) loading="lazy" @endif
                                        decoding="async"
                                    >
                                </a>
                            </article>
                        @endforeach
                    </div>

                    @if ($heroComics->count() > 1)
                        <button type="button" class="catalog-slider-arrow catalog-slider-prev" data-catalog-prev aria-label="Show previous comic">←</button>
                        <button type="button" class="catalog-slider-arrow catalog-slider-next" data-catalog-next aria-label="Show next comic">→</button>
                    @endif
                </div>

                @if ($heroComics->count() > 1)
                    <div class="catalog-slider-controls">
                        <div class="catalog-slider-dots" role="tablist" aria-label="Choose featured comic">
                            @foreach ($heroComics as $heroComic)
                                <button
                                    type="button"
                                    data-catalog-dot
                                    data-slide-target="{{ $loop->index }}"
                                    role="tab"
                                    aria-label="Show {{ $heroComic->title }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    @if ($loop->first) data-active @endif
                                ></button>
                            @endforeach
                        </div>
                        <p><span data-catalog-current>01</span> / {{ str_pad((string) $heroComics->count(), 2, '0', STR_PAD_LEFT) }}</p>
                    </div>
                @endif
            </div>
        </section>
    @else
        <section class="catalog-showcase catalog-showcase-empty">
            <div class="container">
                <p class="eyebrow">Catalog</p>
                <h1>Stories are on the way.</h1>
            </div>
        </section>
    @endif

    <section class="section-block catalog-library-section">
        <div class="container">
            <div class="section-heading catalog-library-heading">
                <div>
                    <p class="eyebrow">Complete library</p>
                    <h2>All Comics</h2>
                </div>
                <span>{{ $catalogTotal }} {{ $catalogTotal === 1 ? 'title' : 'titles' }}</span>
            </div>

            @if ($comics->isEmpty())
                <p class="empty-state">No comics published yet.</p>
            @else
                <div class="card-grid four-up">
                    @foreach ($comics as $comic)
                        @include('public.partials.comic-card', ['comic' => $comic])
                    @endforeach
                </div>

                {{ $comics->links() }}
            @endif
        </div>
    </section>
@endsection
