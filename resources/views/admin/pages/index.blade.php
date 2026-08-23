@extends('layouts.app')

@section('title', 'Page Management | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Pages</p>
                <h1>Page Management</h1>
                <p>Comic: <strong>{{ $comic->title }}</strong> / Chapter: <strong>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong></p>
            </div>
            <div class="admin-toolbar">
                <a href="{{ route('admin.comics.chapters.index', $comic) }}" class="btn btn-secondary">Back to Chapter Management</a>
                <a href="{{ route('admin.comics.chapters.pages.create', [$comic, $chapter]) }}" class="btn btn-primary">Create Page</a>
            </div>
        </div>

        @if (session('success'))
            <div class="form-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($pages->isEmpty())
            <section class="admin-card admin-empty-state">
                <h2>No pages found</h2>
                <p>Add the first page to begin building this chapter.</p>
                <a href="{{ route('admin.comics.chapters.pages.create', [$comic, $chapter]) }}" class="btn btn-primary">Create Page</a>
            </section>
        @else
            <section class="admin-card admin-table-card" aria-labelledby="page-catalog-heading">
                <div class="admin-card-header">
                    <h2 id="page-catalog-heading">Page Catalog</h2>
                    <p>{{ $pages->total() }} {{ $pages->total() === 1 ? 'page' : 'pages' }} total</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-page-table">
                        <thead>
                            <tr>
                                <th scope="col">Page #</th>
                                <th scope="col">Title</th>
                                <th scope="col">Stored Image Path</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pages as $page)
                                <tr>
                                    <td data-label="Page #" class="admin-table-title">{{ $page->page_number }}</td>
                                    <td data-label="Title">{{ $page->title ?: 'Untitled' }}</td>
                                    <td data-label="Stored Image Path" class="admin-table-path">{{ $page->image_path }}</td>
                                    <td data-label="Actions">
                                        <div class="admin-actions">
                                            <a href="{{ route('admin.comics.chapters.pages.show', [$comic, $chapter, $page]) }}" class="btn btn-secondary">View</a>
                                            <a href="{{ route('admin.comics.chapters.pages.edit', [$comic, $chapter, $page]) }}" class="btn btn-secondary">Edit</a>
                                            <form method="POST" action="{{ route('admin.comics.chapters.pages.destroy', [$comic, $chapter, $page]) }}" class="inline-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this page?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="admin-pagination">{{ $pages->links() }}</div>
        @endif
    </div>
@endsection
