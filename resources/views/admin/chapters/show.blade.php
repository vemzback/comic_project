<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</title>
</head>
<body>
    <h1>{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</h1>
    <p><a href="{{ route('admin.comics.chapters.index', $comic) }}">Back to chapters</a></p>

    <p>Comic: {{ $comic->title }}</p>
    <p>Chapter Number: {{ $chapter->chapter_number }}</p>
    <p>Slug: {{ $chapter->slug }}</p>
    <p>Published: {{ $chapter->is_published ? 'Yes' : 'No' }}</p>
    <p>Published At: {{ $chapter->published_at?->format('Y-m-d') ?? '—' }}</p>
    <p>Sort Order: {{ $chapter->sort_order }}</p>
</body>
</html>
