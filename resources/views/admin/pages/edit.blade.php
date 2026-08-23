@extends('layouts.app')

@section('title', 'Edit Page | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Page Management</p>
                <h1>Edit Page {{ $page->page_number }}</h1>
                <p>Updating <strong>{{ $comic->title }}</strong> / <strong>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong>.</p>
            </div>
            <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Back to Page Management</a>
        </div>

        @if ($errors->any())
            <div class="form-error-box" role="alert">
                <ul class="form-error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.comics.chapters.pages.update', [$comic, $chapter, $page]) }}" enctype="multipart/form-data" class="admin-card admin-form">
            @csrf
            @method('PUT')

            <fieldset class="admin-form-section">
                <legend>Page Information</legend>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="page_number" class="form-label">Page Number</label>
                        <input id="page_number" type="number" name="page_number" min="1" value="{{ old('page_number', $page->page_number) }}" class="form-control" required>
                        @error('page_number') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="title" class="form-label">Title</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control">
                        @error('title') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="admin-form-section">
                <legend>Page Image</legend>
                <div class="admin-field">
                    <label for="image_path" class="form-label">Replace Page Image</label>
                    <input id="image_path" type="file" name="image_path" accept="image/*" class="form-control admin-file-input">
                    @if ($page->image_path)
                        <p class="admin-current-file">Current image path: <span>{{ $page->image_path }}</span></p>
                    @else
                        <p class="admin-current-file">No image is currently assigned.</p>
                    @endif
                    @error('image_path') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                </div>
            </fieldset>

            <div class="admin-form-actions">
                <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Page</button>
            </div>
        </form>
    </div>
@endsection
