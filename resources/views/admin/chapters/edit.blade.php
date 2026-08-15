<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Chapter</title>
</head>
<body>
    <h1>Edit Chapter</h1>
    <p><a href="{{ route('admin.comics.chapters.index', $comic) }}">Back to chapters</a></p>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('admin.comics.chapters.update', [$comic, $chapter]) }}">
        @csrf
        @method('PUT')

        <div>
            <label for="chapter_number">Chapter Number</label>
            <input id="chapter_number" type="number" name="chapter_number" min="1" value="{{ old('chapter_number', $chapter->chapter_number) }}" required>
        </div>

        <div>
            <label for="title">Title</label>
            <input id="title" type="text" name="title" value="{{ old('title', $chapter->title) }}" required>
        </div>

        <div>
            <label for="slug">Slug</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $chapter->slug) }}" required>
        </div>

        <div>
            <label for="sort_order">Sort Order</label>
            <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $chapter->sort_order) }}">
        </div>

        <div>
            <label for="is_published">Published</label>
            <input id="is_published" type="checkbox" name="is_published" value="1" {{ old('is_published', $chapter->is_published) ? 'checked' : '' }}>
        </div>

        <div>
            <label for="published_at">Published At</label>
            <input id="published_at" type="date" name="published_at" value="{{ old('published_at', $chapter->published_at?->format('Y-m-d')) }}">
        </div>

        <button type="submit">Update Chapter</button>
    </form>
</body>
</html>
