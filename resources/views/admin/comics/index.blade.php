@extends('layouts.app')

@section('title', 'Comic Management | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area</p>
                <h1>Comic Management</h1>
                <p>Manage the catalog, publishing status, and featured titles.</p>
            </div>
            <div class="admin-toolbar">
                <p>{{ $comics->total() }} {{ $comics->total() === 1 ? 'comic' : 'comics' }} total</p>
                <a href="{{ route('admin.comics.import.index') }}" class="btn btn-secondary">Import from API</a>
                <a href="{{ route('admin.comics.create') }}" class="btn btn-primary">Create Comic</a>
            </div>
        </div>

        @if (session('success'))
            <div class="form-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="form-error-box" role="alert">
                <ul class="form-error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($comics->isEmpty())
            <section class="admin-card admin-empty-state">
                <h2>No comics found</h2>
                <p>Start building the catalog by adding the first comic.</p>
                <a href="{{ route('admin.comics.create') }}" class="btn btn-primary">Create Comic</a>
            </section>
        @else
            <section class="admin-card admin-table-card" aria-labelledby="comic-catalog-heading">
                <div class="admin-card-header">
                    <div>
                        <h2 id="comic-catalog-heading">Comic Catalog</h2>
                        <p>{{ $comics->total() }} {{ $comics->total() === 1 ? 'comic' : 'comics' }} total</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Title</th>
                                <th scope="col">Status</th>
                                <th scope="col">Featured</th>
                                <th scope="col">Genres</th>
                                <th scope="col">Readiness</th>
                                <th scope="col">Published</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comics as $comic)
                                <tr>
                                    <td data-label="ID">{{ $comic->id }}</td>
                                    <td data-label="Title" class="admin-table-title">{{ $comic->title }}</td>
                                    <td data-label="Status"><span class="status-badge status-{{ $comic->status }}">{{ ucfirst($comic->status) }}</span></td>
                                    <td data-label="Featured">{{ $comic->is_featured ? 'Yes' : 'No' }}</td>
                                    <td data-label="Genres" class="admin-table-genres">{{ $comic->genres->pluck('name')->implode(', ') ?: 'None' }}</td>
                                    <td data-label="Readiness">
                                        @php($comicReadiness = $publicationReadiness[$comic->id])
                                        <span class="readiness-badge {{ $comicReadiness['ready'] ? 'is-ready' : 'is-blocked' }}">
                                            {{ $comicReadiness['ready'] ? 'Ready' : count($comicReadiness['blockers']).' '.(count($comicReadiness['blockers']) === 1 ? 'issue' : 'issues') }}
                                        </span>
                                    </td>
                                    <td data-label="Published">{{ $comic->published_at?->format('M j, Y') ?? 'Unpublished' }}</td>
                                    <td data-label="Actions">
                                        <div class="admin-actions">
                                            <a href="{{ route('admin.comics.show', $comic) }}" class="btn btn-secondary">View</a>
                                            <a href="{{ route('admin.comics.chapters.index', $comic) }}" class="btn btn-secondary">Chapters</a>
                                            <a href="{{ route('admin.comics.edit', $comic) }}" class="btn btn-secondary">Edit</a>
                                            @if (! $comic->published_at)
                                                <form method="POST" action="{{ route('admin.comics.destroy', $comic) }}" class="inline-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this comic?')">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="admin-pagination">{{ $comics->links() }}</div>
        @endif
    </div>
@endsection
