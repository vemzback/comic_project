@extends('layouts.app')

@section('title', ($page->title ?: 'Page ' . $page->page_number) . ' | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Pages</p>
                <h1>{{ $page->title ?: 'Page ' . $page->page_number }}</h1>
                <p>Comic: <strong>{{ $comic->title }}</strong> / Chapter: <strong>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong></p>
            </div>
            <div class="admin-toolbar">
                <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Back to Page Management</a>
                <a href="{{ route('admin.comics.chapters.pages.edit', [$comic, $chapter, $page]) }}" class="btn btn-primary">Edit Page</a>
            </div>
        </div>

        <section class="admin-card admin-detail-card" aria-labelledby="page-details-heading">
            <div class="admin-card-header">
                <h2 id="page-details-heading">Page Details</h2>
                <p>Read-only information for this page.</p>
            </div>
            <dl class="admin-detail-grid">
                <div class="admin-detail-item">
                    <dt>Page Number</dt>
                    <dd>{{ $page->page_number }}</dd>
                </div>
                <div class="admin-detail-item">
                    <dt>Title</dt>
                    <dd>{{ $page->title ?: 'Untitled' }}</dd>
                </div>
                <div class="admin-detail-item admin-detail-item-wide">
                    <dt>Stored Image Path</dt>
                    <dd>{{ $page->image_path }}</dd>
                </div>
            </dl>
        </section>
    </div>
@endsection
