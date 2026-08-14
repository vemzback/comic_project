<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comic Management</title>
</head>
<body>
    <h1>Comic Management</h1>

    <p><a href="{{ route('admin.dashboard') }}">Back to dashboard</a></p>
    <p><a href="{{ route('admin.comics.create') }}">Create Comic</a></p>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if ($comics->isEmpty())
        <p>No comics found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Genres</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comics as $comic)
                    <tr>
                        <td>{{ $comic->id }}</td>
                        <td>{{ $comic->title }}</td>
                        <td>{{ ucfirst($comic->status) }}</td>
                        <td>{{ $comic->is_featured ? 'Yes' : 'No' }}</td>
                        <td>{{ $comic->genres->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td>
                            <a href="{{ route('admin.comics.edit', $comic) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.comics.destroy', $comic) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this comic?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $comics->links() }}
    @endif
</body>
</html>
