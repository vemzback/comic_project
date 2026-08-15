<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Management</title>
</head>
<body>
    <h1>Page Management</h1>
    <p><a href="{{ route('admin.comics.chapters.index', $comic) }}">Back to chapters</a></p>
    <p><a href="{{ route('admin.comics.chapters.pages.create', [$comic, $chapter]) }}">Create Page</a></p>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if ($pages->isEmpty())
        <p>No pages found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Title</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pages as $page)
                    <tr>
                        <td>{{ $page->page_number }}</td>
                        <td>{{ $page->title ?: 'Untitled' }}</td>
                        <td>{{ $page->image_path }}</td>
                        <td>
                            <a href="{{ route('admin.comics.chapters.pages.show', [$comic, $chapter, $page]) }}">View</a>
                            <a href="{{ route('admin.comics.chapters.pages.edit', [$comic, $chapter, $page]) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.comics.chapters.pages.destroy', [$comic, $chapter, $page]) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this page?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $pages->links() }}
    @endif
</body>
</html>
