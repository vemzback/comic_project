@extends('layouts.app')

@section('title', 'User Management | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Users</p>
                <h1>User Management</h1>
                <p>Manage registered accounts and roles.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        @if (session('status'))
            <div class="form-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.users.index') }}" class="admin-card admin-filter-form">
            <div class="admin-form-grid">
                <div class="admin-field">
                    <label for="search" class="form-label">Search by Name or Email</label>
                    <input type="search" name="search" id="search" value="{{ old('search', $search) }}" placeholder="Search by name or email..." class="form-control">
                </div>

                <div class="admin-field">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-control">
                        <option value="">All Roles</option>
                        <option value="user" @if(old('role', $role) === 'user') selected @endif>User</option>
                        <option value="admin" @if(old('role', $role) === 'admin') selected @endif>Admin</option>
                    </select>
                </div>
            </div>

            <div class="admin-form-actions admin-filter-actions">
                <button type="submit" class="btn btn-primary">Search Users</button>
                @if($search || $role)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Clear Filters</a>
                @endif
            </div>
        </form>

        @if ($users->isEmpty())
            <section class="admin-card admin-empty-state">
                @if ($search || $role)
                    <h2>No users match your search criteria</h2>
                    <p>Try adjusting the search or role filter.</p>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Clear Filters</a>
                @else
                    <h2>No users found</h2>
                    <p>Registered accounts will appear here.</p>
                @endif
            </section>
        @else
            <section class="admin-card admin-table-card" aria-labelledby="user-catalog-heading">
                <div class="admin-card-header">
                    <h2 id="user-catalog-heading">Registered Accounts</h2>
                    <p>{{ $users->total() }} {{ $users->total() === 1 ? 'user' : 'users' }} total</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-user-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Joined</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td data-label="ID">{{ $user->id }}</td>
                                    <td data-label="Name" class="admin-table-title">{{ $user->name }}</td>
                                    <td data-label="Email" class="admin-table-path">{{ $user->email }}</td>
                                    <td data-label="Role"><span class="status-badge {{ $user->role === 'admin' ? 'status-admin' : 'status-user' }}">{{ ucfirst($user->role) }}</span></td>
                                    <td data-label="Joined">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                    <td data-label="Actions">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">View & Manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="admin-pagination">{{ $users->links() }}</div>
        @endif
    </div>
@endsection

