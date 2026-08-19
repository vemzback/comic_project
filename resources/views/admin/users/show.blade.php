<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->name }} - User Details</title>
</head>
<body>
    <h1>{{ $user->name }}</h1>
    <p><a href="{{ route('admin.users.index') }}">Back to list</a></p>

    <h2>Account Information</h2>
    <p>ID: {{ $user->id }}</p>
    <p>Name: {{ $user->name }}</p>
    <p>Email: {{ $user->email }}</p>
    <p>Role: {{ ucfirst($user->role) }}</p>
    <p>Created: {{ $user->created_at->format('Y-m-d H:i:s') }}</p>
    <p>Updated: {{ $user->updated_at->format('Y-m-d H:i:s') }}</p>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Role Management</h2>
    <p>Current Role: {{ ucfirst($user->role) }}</p>

    @if (auth()->id() === $user->id)
        <p>You cannot change your own role.</p>
    @else
        <form method="POST" action="{{ route('admin.users.role.update', $user) }}">
            @csrf
            @method('PUT')

            <label for="role">Role</label>
            <select name="role" id="role">
                <option value="user" @selected($user->role === 'user')>User</option>
                <option value="admin" @selected($user->role === 'admin')>Admin</option>
            </select>

            <button type="submit">Update Role</button>
        </form>
    @endif

    <h2>Activity</h2>
    <p>Bookmarks: {{ $user->bookmarks_count }}</p>
    <p>Reading Histories: {{ $user->reading_histories_count }}</p>
    <p>Comments: {{ $user->comments_count }}</p>
    <p>Ratings: {{ $user->ratings_count }}</p>
</body>
</html>
