@extends('layouts.app')

@section('title', 'Bulk Import Pages | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Pages</p>
                <h1>Bulk Import Pages</h1>
                <p>Comic: <strong>{{ $comic->title }}</strong> / Chapter: <strong>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</strong></p>
            </div>
            <div class="admin-toolbar">
                <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Back to Page Management</a>
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

        <section class="admin-card bulk-import-card" aria-labelledby="bulk-import-heading">
            <div class="admin-card-header">
                <h2 id="bulk-import-heading">Upload Chapter Archive</h2>
                <p>The archive is staged privately so you can review the page order before saving it.</p>
            </div>

            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.comics.chapters.pages.bulk.preview', [$comic, $chapter]) }}" enctype="multipart/form-data" class="admin-form">
                    @csrf

                    <div class="admin-field">
                        <label for="archive" class="form-label">ZIP or CBZ File</label>
                        <input id="archive" type="file" name="archive" accept=".zip,.cbz,application/zip,application/x-zip-compressed" class="form-control admin-file-input" required>
                        <p class="bulk-import-help">Maximum upload: 38 MB. Maximum pages: 300. Supported images: JPG, PNG, and WebP.</p>
                        @error('archive') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="bulk-import-guide" aria-label="Archive preparation guide">
                        <h3>Before uploading</h3>
                        <ol>
                            <li>Use one archive for one chapter.</li>
                            <li>Name files in reading order, such as 001.jpg, 002.jpg, and 003.jpg.</li>
                            <li>Remove unrelated documents and duplicate filenames.</li>
                            <li>The chapter must not contain existing pages.</li>
                        </ol>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn btn-primary">Upload and Preview</button>
                        <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
