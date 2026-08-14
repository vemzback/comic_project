<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Comic</title>
</head>
<body>
    <h1>Edit Comic</h1>
    <p><a href="{{ route('admin.comics.index') }}">Back to list</a></p>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('admin.comics.update', $comic) }}">
        @csrf
        @method('PUT')

        <div>
            <label for="title">Title</label>
            <input id="title" type="text" name="title" value="{{ old('title', $comic->title) }}" required>
        </div>

        <div>
            <label for="slug">Slug</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $comic->slug) }}" required>
        </div>

        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description', $comic->description) }}</textarea>
        </div>

        <div>
            <label for="cover_image">Cover Image</label>
            <input id="cover_image" type="text" name="cover_image" value="{{ old('cover_image', $comic->cover_image) }}">
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="ongoing" {{ old('status', $comic->status) === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                <option value="completed" {{ old('status', $comic->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="hiatus" {{ old('status', $comic->status) === 'hiatus' ? 'selected' : '' }}>Hiatus</option>
            </select>
        </div>

        <div>
            <label for="published_at">Published At</label>
            <input id="published_at" type="date" name="published_at" value="{{ old('published_at', $comic->published_at?->format('Y-m-d')) }}">
        </div>

        <div>
            <label for="is_featured">Featured</label>
            <input id="is_featured" type="checkbox" name="is_featured" value="1" {{ old('is_featured', $comic->is_featured) ? 'checked' : '' }}>
        </div>

        <div>
            <label for="seo_title">SEO Title</label>
            <input id="seo_title" type="text" name="seo_title" value="{{ old('seo_title', $comic->seo_title) }}">
        </div>

        <div>
            <label for="seo_description">SEO Description</label>
            <textarea id="seo_description" name="seo_description">{{ old('seo_description', $comic->seo_description) }}</textarea>
        </div>

        <div>
            <label>Genres</label>
            @foreach ($genres as $genre)
                <label>
                    <input type="checkbox" name="genres[]" value="{{ $genre->id }}" {{ in_array((string) $genre->id, old('genres', $comic->genres->pluck('id')->map(fn ($id) => (string) $id)->all()), true) ? 'checked' : '' }}>
                    {{ $genre->name }}
                </label>
            @endforeach
        </div>

        <button type="submit">Update Comic</button>
    </form>
</body>
</html>
