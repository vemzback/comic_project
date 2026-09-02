@extends('layouts.app')

@section('title', 'Edit Chapter | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Chapter Management</p>
                <h1>Edit Chapter</h1>
                <p>Updating Chapter {{ $chapter->chapter_number }} for <strong>{{ $comic->title }}</strong>.</p>
            </div>
            <a href="{{ route('admin.comics.chapters.index', $comic) }}" class="btn btn-secondary">Back to Chapter Management</a>
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

        <section class="admin-card chapter-readiness {{ $chapterReadiness['ready'] ? 'publication-readiness-ready' : 'publication-readiness-blocked' }}">
            <div>
                <p class="eyebrow">Publishing Safety</p>
                <h2>{{ $chapterReadiness['ready'] ? 'Chapter ready to publish' : 'Chapter not ready yet' }}</h2>
            </div>
            @if (! $chapterReadiness['ready'])
                <ul>
                    @foreach ($chapterReadiness['blockers'] as $blocker)
                        <li>{{ ucfirst($blocker) }}</li>
                    @endforeach
                </ul>
            @else
                <p>Its publication date, page order, and stored images have passed the required checks.</p>
            @endif
        </section>

        <form method="POST" action="{{ route('admin.comics.chapters.update', [$comic, $chapter]) }}" class="admin-card admin-form">
            @csrf
            @method('PUT')

            <fieldset class="admin-form-section">
                <legend>Chapter Information</legend>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="chapter_number" class="form-label">Chapter Number</label>
                        <input id="chapter_number" type="number" name="chapter_number" min="1" value="{{ old('chapter_number', $chapter->chapter_number) }}" class="form-control" required>
                        @error('chapter_number') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="title" class="form-label">Title</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $chapter->title) }}" class="form-control" required>
                        @error('title') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field admin-field-wide">
                        <label for="slug" class="form-label">Slug</label>
                        <input id="slug" type="text" name="slug" value="{{ old('slug', $chapter->slug) }}" class="form-control" required>
                        @error('slug') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="admin-form-section">
                <legend>Publishing</legend>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="published_at" class="form-label">Published At</label>
                        <input id="published_at" type="date" name="published_at" value="{{ old('published_at', $chapter->published_at?->format('Y-m-d')) }}" class="form-control">
                        @error('published_at') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $chapter->sort_order) }}" class="form-control">
                        @error('sort_order') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field admin-checkbox-field">
                        <label class="admin-checkbox-label">
                            <input id="is_published" type="checkbox" name="is_published" value="1" {{ old('is_published', $chapter->is_published) ? 'checked' : '' }}>
                            <span>Published chapter</span>
                        </label>
                        <small class="admin-field-help">Publishing is accepted only when all chapter readiness checks pass.</small>
                        @error('is_published') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>

            <div class="admin-form-actions">
                <a href="{{ route('admin.comics.chapters.index', $comic) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Chapter</button>
            </div>
        </form>
    </div>
@endsection
