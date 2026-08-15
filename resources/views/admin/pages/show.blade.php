<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title ?: 'Page ' . $page->page_number }}</title>
</head>
<body>
    <h1>{{ $page->title ?: 'Page ' . $page->page_number }}</h1>
    <p><a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}">Back to pages</a></p>

    <p>Comic: {{ $comic->title }}</p>
    <p>Chapter: {{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</p>
    <p>Page Number: {{ $page->page_number }}</p>
    <p>Image Path: {{ $page->image_path }}</p>
</body>
</html>
