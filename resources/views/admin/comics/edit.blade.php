@extends('layouts.app')

@section('title', 'Edit Comic | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Comic Management</p>
                <h1>Edit Comic</h1>
                <p>Update {{ $comic->title }} and its catalog details.</p>
            </div>
            <a href="{{ route('admin.comics.index') }}" class="btn btn-secondary">Back to Comic Management</a>
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

        @include('admin.comics._readiness')

        <form method="POST" action="{{ route('admin.comics.update', $comic) }}" enctype="multipart/form-data" class="admin-card admin-form">
            @csrf
            @method('PUT')

            <fieldset class="admin-form-section">
                <legend>Basic Information</legend>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="title" class="form-label">Title</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $comic->title) }}" class="form-control" required>
                        @error('title') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="slug" class="form-label">Slug</label>
                        <input id="slug" type="text" name="slug" value="{{ old('slug', $comic->slug) }}" class="form-control" required>
                        @error('slug') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field admin-field-wide">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control">{{ old('description', $comic->description) }}</textarea>
                        @error('description') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="author" class="form-label">Author / Creator</label>
                        <input id="author" type="text" name="author" value="{{ old('author', $comic->author) }}" class="form-control">
                        @error('author') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input id="publisher" type="text" name="publisher" value="{{ old('publisher', $comic->publisher) }}" class="form-control">
                        @error('publisher') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="original_published_at" class="form-label">Original Publication Date</label>
                        <input id="original_published_at" type="date" name="original_published_at" value="{{ old('original_published_at', $comic->original_published_at?->format('Y-m-d')) }}" class="form-control">
                        @error('original_published_at') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="admin-form-section">
                <legend>Publishing</legend>
                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="ongoing" {{ old('status', $comic->status) === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ old('status', $comic->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="hiatus" {{ old('status', $comic->status) === 'hiatus' ? 'selected' : '' }}>Hiatus</option>
                        </select>
                        @error('status') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field">
                        <label for="published_at" class="form-label">Published At</label>
                        <input id="published_at" type="date" name="published_at" value="{{ old('published_at', $comic->published_at?->format('Y-m-d')) }}" class="form-control">
                        <small class="admin-field-help">
                            {{ $publicationReadiness['ready']
                                ? 'All required checks pass. Add a date to publish or schedule this comic.'
                                : 'Publishing is locked until the blocking issues above are completed.' }}
                        </small>
                        @error('published_at') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field admin-checkbox-field">
                        <label class="admin-checkbox-label">
                            <input id="is_featured" type="checkbox" name="is_featured" value="1" {{ old('is_featured', $comic->is_featured) ? 'checked' : '' }}>
                            <span>Featured comic</span>
                        </label>
                        @error('is_featured') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="admin-form-section">
                <legend>Cover</legend>
                <div class="admin-field">
                    <label for="cover_image" class="form-label">Replace Cover Image</label>
                    <input id="cover_image" type="file" name="cover_image" accept="image/*" class="form-control admin-file-input">
                    @if ($comic->cover_image)
                        <p class="admin-current-file">Current cover: <span>{{ $comic->cover_image }}</span></p>
                    @else
                        <p class="admin-current-file">No cover image is currently assigned.</p>
                    @endif
                    @error('cover_image') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                </div>
            </fieldset>

            <fieldset class="admin-form-section">
                <legend>SEO</legend>
                <div class="admin-form-grid">
                    <div class="admin-field admin-field-wide">
                        <label for="seo_title" class="form-label">SEO Title</label>
                        <input id="seo_title" type="text" name="seo_title" value="{{ old('seo_title', $comic->seo_title) }}" class="form-control">
                        @error('seo_title') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="admin-field admin-field-wide">
                        <label for="seo_description" class="form-label">SEO Description</label>
                        <textarea id="seo_description" name="seo_description" class="form-control">{{ old('seo_description', $comic->seo_description) }}</textarea>
                        @error('seo_description') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="admin-form-section">
                <legend>Genres</legend>
                <div class="genre-checkbox-grid">
                    @foreach ($genres as $genre)
                        <label class="admin-checkbox-label">
                            <input type="checkbox" name="genres[]" value="{{ $genre->id }}" {{ in_array((string) $genre->id, old('genres', $comic->genres->pluck('id')->map(fn ($id) => (string) $id)->all()), true) ? 'checked' : '' }}>
                            <span>{{ $genre->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('genres') <span class="form-error" role="alert">{{ $message }}</span> @enderror
                @error('genres.*') <span class="form-error" role="alert">{{ $message }}</span> @enderror
            </fieldset>

            <div class="admin-form-actions">
                <a href="{{ route('admin.comics.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Comic</button>
            </div>
        </form>
    </div>
@endsection
