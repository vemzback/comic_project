@extends('layouts.app')

@section('title', ($chapter->title ?: 'Chapter ' . $chapter->chapter_number) . ' | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Chapters</p>
                <h1>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</h1>
                <p>Comic: <strong>{{ $comic->title }}</strong></p>
            </div>
            <div class="admin-toolbar">
                <a href="{{ route('admin.comics.chapters.index', $comic) }}" class="btn btn-secondary">Back to Chapter Management</a>
                <a href="{{ route('admin.comics.chapters.edit', [$comic, $chapter]) }}" class="btn btn-primary">Edit Chapter</a>
                <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Manage Pages</a>
            </div>
        </div>

        <section class="admin-card admin-detail-card" aria-labelledby="chapter-details-heading">
            <div class="admin-card-header">
                <h2 id="chapter-details-heading">Chapter Details</h2>
                <p>Read-only information for this chapter.</p>
            </div>
            <dl class="admin-detail-grid">
                <div class="admin-detail-item">
                    <dt>Chapter Number</dt>
                    <dd>{{ $chapter->chapter_number }}</dd>
                </div>
                <div class="admin-detail-item">
                    <dt>Title</dt>
                    <dd>{{ $chapter->title ?: 'Untitled' }}</dd>
                </div>
                <div class="admin-detail-item">
                    <dt>Slug</dt>
                    <dd>{{ $chapter->slug }}</dd>
                </div>
                <div class="admin-detail-item">
                    <dt>Publication Status</dt>
                    <dd>
                        <span class="status-badge {{ $chapter->is_published ? 'status-published' : 'status-draft' }}">
                            {{ $chapter->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </dd>
                </div>
                <div class="admin-detail-item">
                    <dt>Published At</dt>
                    <dd>{{ $chapter->published_at?->format('Y-m-d') ?? 'Not published' }}</dd>
                </div>
                <div class="admin-detail-item">
                    <dt>Sort Order</dt>
                    <dd>{{ $chapter->sort_order }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
