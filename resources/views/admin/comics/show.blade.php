@extends('layouts.app')

@section('title', $comic->title . ' | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Comics</p>
                <h1>{{ $comic->title }}</h1>
                <p>Review the catalog information and publishing details for this comic.</p>
            </div>
            <div class="admin-toolbar">
                <a href="{{ route('admin.comics.index') }}" class="btn btn-secondary">Back to Comic Management</a>
                <a href="{{ route('admin.comics.edit', $comic) }}" class="btn btn-primary">Edit Comic</a>
                <a href="{{ route('admin.comics.chapters.index', $comic) }}" class="btn btn-secondary">Manage Chapters</a>
            </div>
        </div>

        @include('admin.comics._readiness')

        <div class="admin-detail-layout">
            <section class="admin-card admin-detail-card" aria-labelledby="comic-details-heading">
                <div class="admin-card-header">
                    <h2 id="comic-details-heading">Comic Details</h2>
                    <p>Read-only catalog and publication information.</p>
                </div>
                <dl class="admin-detail-grid">
                    <div class="admin-detail-item">
                        <dt>Status</dt>
                        <dd><span class="status-badge status-{{ $comic->status }}">{{ ucfirst($comic->status) }}</span></dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Featured</dt>
                        <dd>{{ $comic->is_featured ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Published At</dt>
                        <dd>{{ $comic->published_at?->format('M j, Y') ?? 'Not published' }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Chapters</dt>
                        <dd>{{ $comic->chapters->count() }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Genres</dt>
                        <dd>{{ $comic->genres->pluck('name')->implode(', ') ?: 'None assigned' }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Slug</dt>
                        <dd>{{ $comic->slug }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Author / Creator</dt>
                        <dd>{{ $comic->author ?: 'Not provided' }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Publisher</dt>
                        <dd>{{ $comic->publisher ?: 'Not provided' }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Original Publication</dt>
                        <dd>{{ $comic->original_published_at?->format('M j, Y') ?? 'Not provided' }}</dd>
                    </div>
                    <div class="admin-detail-item">
                        <dt>Metadata Source</dt>
                        <dd>
                            @if ($comic->source_url)
                                <a href="{{ $comic->source_url }}" target="_blank" rel="noreferrer noopener">
                                    {{ $comic->external_provider ? str_replace('_', ' ', ucfirst($comic->external_provider)) : 'View source' }}
                                </a>
                            @else
                                Manual entry
                            @endif
                        </dd>
                    </div>
                    <div class="admin-detail-item admin-detail-item-wide">
                        <dt>Description</dt>
                        <dd>{{ $comic->description ?: 'No description provided.' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-card admin-detail-card" aria-labelledby="comic-seo-heading">
                <div class="admin-card-header">
                    <h2 id="comic-seo-heading">Search Preview Content</h2>
                    <p>SEO content currently stored for this comic.</p>
                </div>
                <dl class="admin-detail-grid">
                    <div class="admin-detail-item admin-detail-item-wide">
                        <dt>SEO Title</dt>
                        <dd>{{ $comic->seo_title ?: 'Not provided' }}</dd>
                    </div>
                    <div class="admin-detail-item admin-detail-item-wide">
                        <dt>SEO Description</dt>
                        <dd>{{ $comic->seo_description ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
@endsection
