<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comic->title }}</title>
</head>
<body>
    <h1>{{ $comic->title }}</h1>
    <p><a href="{{ route('admin.comics.index') }}">Back to list</a></p>

    <p>Status: {{ ucfirst($comic->status) }}</p>
    <p>Featured: {{ $comic->is_featured ? 'Yes' : 'No' }}</p>
    <p>Published: {{ $comic->published_at?->format('Y-m-d') ?? 'N/A' }}</p>
    <p>{{ $comic->description }}</p>
</body>
</html>
