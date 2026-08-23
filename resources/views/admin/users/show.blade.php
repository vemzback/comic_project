@extends('layouts.app')

@section('title', $user->name . ' | User Details')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Users</p>
                <h1>{{ $user->name }}</h1>
                <p>User account details and role management.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to User Management</a>
        </div>

        @if (session('status'))
            <div class="form-success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="form-error-box" role="alert">
                <ul class="form-error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-detail-layout">
            <section class="admin-card admin-detail-card" aria-labelledby="account-information-heading">
                <div class="admin-card-header">
                    <h2 id="account-information-heading">Account Information</h2>
                    <p>Read-only account identity details.</p>
                </div>
                <dl class="admin-detail-grid">
                    <div class="admin-detail-item"><dt>ID</dt><dd>{{ $user->id }}</dd></div>
                    <div class="admin-detail-item"><dt>Name</dt><dd>{{ $user->name }}</dd></div>
                    <div class="admin-detail-item"><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                    <div class="admin-detail-item"><dt>Role</dt><dd><span class="status-badge {{ $user->role === 'admin' ? 'status-admin' : 'status-user' }}">{{ ucfirst($user->role) }}</span></dd></div>
                    <div class="admin-detail-item"><dt>Joined At</dt><dd>{{ $user->created_at->format('Y-m-d H:i:s') }}</dd></div>
                    <div class="admin-detail-item"><dt>Updated At</dt><dd>{{ $user->updated_at->format('Y-m-d H:i:s') }}</dd></div>
                </dl>
            </section>

            <section class="admin-card admin-role-card" aria-labelledby="role-management-heading">
                <div class="admin-card-header">
                    <h2 id="role-management-heading">Role Management</h2>
                    <p>Current role: <span class="status-badge {{ $user->role === 'admin' ? 'status-admin' : 'status-user' }}">{{ ucfirst($user->role) }}</span></p>
                </div>
                <div class="admin-card-body">
                    @if (auth()->id() === $user->id)
                        <p class="admin-protected-message">You cannot change your own role.</p>
                    @else
                        <form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="auth-form">
                            @csrf
                            @method('PUT')
                            <div class="admin-field">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" id="role" class="form-control">
                                    <option value="user" @selected($user->role === 'user')>User</option>
                                    <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Role</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="admin-card" aria-labelledby="activity-heading">
                <div class="admin-card-header">
                    <h2 id="activity-heading">Activity</h2>
                    <p>Summary of account activity.</p>
                </div>
                <dl class="admin-activity-grid">
                    <div><dt>Bookmarks</dt><dd>{{ $user->bookmarks_count }}</dd></div>
                    <div><dt>Reading Histories</dt><dd>{{ $user->reading_histories_count }}</dd></div>
                    <div><dt>Comments</dt><dd>{{ $user->comments_count }}</dd></div>
                    <div><dt>Ratings</dt><dd>{{ $user->ratings_count }}</dd></div>
                </dl>
            </section>
        </div>
    </div>
@endsection
