<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
</head>
<body>
    <h1>User Management</h1>

    <p><a href="{{ route('admin.dashboard') }}">Back to dashboard</a></p>

    {{-- Search and Filter Form --}}
    <form method="GET" action="{{ route('admin.users.index') }}" style="margin-bottom: 20px;">
        <fieldset>
            <legend>Search Users</legend>

            <div>
                <label for="search">Search by Name or Email</label>
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ old('search', $search) }}"
                    placeholder="Search by name or email..."
                >
            </div>

            <div>
                <label for="role">Role</label>
                <select name="role" id="role">
                    <option value="">All Roles</option>
                    <option value="user" @if(old('role', $role) === 'user') selected @endif>User</option>
                    <option value="admin" @if(old('role', $role) === 'admin') selected @endif>Admin</option>
                </select>
            </div>

            <button type="submit">Search</button>
            @if($search || $role)
                <a href="{{ route('admin.users.index') }}">Clear</a>
            @endif
        </fieldset>
    </form>

    {{-- Empty State --}}
    @if ($users->isEmpty())
        @if ($search || $role)
            <p>No users match your search criteria.</p>
            <a href="{{ route('admin.users.index') }}">Clear filters</a>
        @else
            <p>No users found.</p>
        @endif
    @else
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucfirst($user->role) }}</td>
                        <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $users->links() }}
    @endif
</body>
</html>

