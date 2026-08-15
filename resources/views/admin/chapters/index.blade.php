<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter Management</title>
</head>
<body>
    <h1>Chapter Management</h1>
    <p><a href="{{ route('admin.comics.index') }}">Back to comic list</a></p>
    <p><a href="{{ route('admin.comics.chapters.create', $comic) }}">Create Chapter</a></p>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if ($chapters->isEmpty())
        <p>No chapters found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($chapters as $chapter)
                    <tr>
                        <td>{{ $chapter->chapter_number }}</td>
                        <td>{{ $chapter->title ?: 'Untitled' }}</td>
                        <td>{{ $chapter->is_published ? 'Published' : 'Draft' }}</td>
                        <td>{{ $chapter->published_at?->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.comics.chapters.show', [$comic, $chapter]) }}">View</a>
                            <a href="{{ route('admin.comics.chapters.edit', [$comic, $chapter]) }}">Edit</a>
                            <a href="{{ route('admin.comics.chapters.pages.index', [$comic, $chapter]) }}">Pages</a>
                            <form method="POST" action="{{ route('admin.comics.chapters.destroy', [$comic, $chapter]) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this chapter?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $chapters->links() }}
    @endif
</body>
</html>
