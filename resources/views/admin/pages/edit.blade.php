<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Page</title>
</head>
<body>
    <h1>Edit Page</h1>
    <p><a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}">Back to pages</a></p>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('admin.comics.chapters.pages.update', [$comic, $chapter, $page]) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <label for="page_number">Page Number</label>
            <input id="page_number" type="number" name="page_number" min="1" value="{{ old('page_number', $page->page_number) }}" required>
        </div>

        <div>
            <label for="title">Title</label>
            <input id="title" type="text" name="title" value="{{ old('title', $page->title) }}">
        </div>

        <div>
            <label for="image_path">Page Image</label>
            <input id="image_path" type="file" name="image_path" accept="image/*">
            @if ($page->image_path)
                <p>Current: {{ $page->image_path }}</p>
            @endif
        </div>

        <button type="submit">Update Page</button>
    </form>
</body>
</html>
