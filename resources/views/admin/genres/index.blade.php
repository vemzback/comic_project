@extends('layouts.app')

@section('title', 'Genre Management | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area</p>
                <h1>Genre Management</h1>
                <p>Manage the genres used across the comic catalog.</p>
            </div>
            <div class="admin-toolbar">
                <p>{{ $genres->total() }} {{ $genres->total() === 1 ? 'genre' : 'genres' }} total</p>
                <a href="{{ route('admin.genres.create') }}" class="btn btn-primary">Create Genre</a>
            </div>
        </div>

        @if (session('success'))
            <div class="form-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($genres->isEmpty())
            <section class="admin-card admin-empty-state">
                <h2>No genres found</h2>
                <p>Start organizing the catalog by adding the first genre.</p>
                <a href="{{ route('admin.genres.create') }}" class="btn btn-primary">Create Genre</a>
            </section>
        @else
            <section class="admin-card admin-table-card" aria-labelledby="genre-list-heading">
                <div class="admin-card-header">
                    <div>
                        <h2 id="genre-list-heading">Genres</h2>
                        <p>{{ $genres->total() }} {{ $genres->total() === 1 ? 'genre' : 'genres' }} total</p>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Slug</th>
                                <th scope="col">Comics</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($genres as $genre)
                                <tr>
                                    <td data-label="Name" class="admin-table-title">{{ $genre->name }}</td>
                                    <td data-label="Slug">{{ $genre->slug }}</td>
                                    <td data-label="Comics">{{ $genre->comics_count }}</td>
                                    <td data-label="Actions">
                                        <div class="admin-actions">
                                            <a href="{{ route('admin.genres.edit', $genre) }}" class="btn btn-secondary">Edit</a>
                                            <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}" class="inline-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this genre?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="admin-pagination">{{ $genres->links() }}</div>
        @endif
    </div>
@endsection