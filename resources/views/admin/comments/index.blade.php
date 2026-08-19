<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Moderation</title>
</head>
<body>
    <h1>Comment Moderation</h1>

    <p><a href="{{ route('admin.dashboard') }}">Back to dashboard</a></p>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <form method="GET" action="{{ route('admin.comments.index') }}" style="margin-bottom: 20px;">
        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="approved" @selected($status === 'approved')>Approved</option>
            <option value="all" @selected($status === 'all')>All</option>
        </select>
        <button type="submit">Apply Filter</button>
    </form>

    @if ($comments->isEmpty())
        @if ($status === 'pending')
            <p>No pending comments.</p>
        @elseif ($status === 'approved')
            <p>No approved comments.</p>
        @else
            <p>No comments found.</p>
        @endif
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Author</th>
                    <th>Comic</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comments as $comment)
                    <tr>
                        <td>{{ $comment->id }}</td>
                        <td>
                            {{ $comment->user->name }}<br>
                            <small>{{ $comment->user->email }}</small>
                        </td>
                        <td>{{ $comment->comic->title }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($comment->body, 120) }}</td>
                        <td>{{ $comment->is_approved ? 'Approved' : 'Pending' }}</td>
                        <td>{{ $comment->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.comments.approval.update', $comment) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_approved" value="{{ $comment->is_approved ? '0' : '1' }}">
                                <button type="submit">{{ $comment->is_approved ? 'Unapprove' : 'Approve' }}</button>
                            </form>

                            <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $comments->links() }}
    @endif
</body>
</html>
