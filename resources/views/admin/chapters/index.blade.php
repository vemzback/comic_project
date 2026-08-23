@extends('layouts.app')

@section('title', 'Chapter Management | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Chapters</p>
                <h1>Chapter Management</h1>
                <p>Comic: <strong>{{ $comic->title }}</strong></p>
            </div>
            <div class="admin-toolbar">
                <a href="{{ route('admin.comics.index') }}" class="btn btn-secondary">Back to Comics</a>
                <a href="{{ route('admin.comics.chapters.create', $comic) }}" class="btn btn-primary">Create Chapter</a>
            </div>
        </div>

        @if (session('success'))
            <div class="form-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($chapters->isEmpty())
            <section class="admin-card admin-empty-state">
                <h2>No chapters found</h2>
                <p>Add the first chapter for this comic to begin building its reader.</p>
                <a href="{{ route('admin.comics.chapters.create', $comic) }}" class="btn btn-primary">Create Chapter</a>
            </section>
        @else
            <section class="admin-card admin-table-card" aria-labelledby="chapter-catalog-heading">
                <div class="admin-card-header">
                    <h2 id="chapter-catalog-heading">Chapter Catalog</h2>
                    <p>{{ $chapters->total() }} {{ $chapters->total() === 1 ? 'chapter' : 'chapters' }} total</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-chapter-table">
                        <thead>
                            <tr>
                                <th scope="col">Chapter #</th>
                                <th scope="col">Title</th>
                                <th scope="col">Slug</th>
                                <th scope="col">Published</th>
                                <th scope="col">Sort Order</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($chapters as $chapter)
                                <tr>
                                    <td data-label="Chapter #" class="admin-table-title">{{ $chapter->chapter_number }}</td>
                                    <td data-label="Title">{{ $chapter->title ?: 'Untitled' }}</td>
                                    <td data-label="Slug" class="admin-table-genres">{{ $chapter->slug }}</td>
                                    <td data-label="Published">
                                        <span class="status-badge {{ $chapter->is_published ? 'status-published' : 'status-draft' }}">
                                            {{ $chapter->is_published ? 'Published' : 'Draft' }}
                                        </span>
                                        <small class="admin-table-meta">{{ $chapter->published_at?->format('M j, Y') ?? 'No date' }}</small>
                                    </td>
                                    <td data-label="Sort Order">{{ $chapter->sort_order }}</td>
                                    <td data-label="Actions">
                                        <div class="admin-actions">
                                            <a href="{{ route('admin.comics.chapters.show', [$comic, $chapter]) }}" class="btn btn-secondary">View</a>
                                            <a href="{{ route('admin.comics.chapters.edit', [$comic, $chapter]) }}" class="btn btn-secondary">Edit</a>
                                            <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Pages</a>
                                            <form method="POST" action="{{ route('admin.comics.chapters.destroy', [$comic, $chapter]) }}" class="inline-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this chapter?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="admin-pagination">{{ $chapters->links() }}</div>
        @endif
    </div>
@endsection
