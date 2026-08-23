@extends('layouts.app')

@section('title', 'Profile | Comic Project')

@section('content')
    <div class="account-shell">
        <div class="account-card">
            <div class="account-header">
                <p class="eyebrow">Your account</p>
                <h1>Profile</h1>
                <p>Manage your account information</p>
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

            <section class="account-section" aria-labelledby="profile-information-heading">
                <h2 id="profile-information-heading">Profile Information</h2>
                <form method="POST" action="{{ route('profile.update') }}" class="auth-form">
                    @csrf

                    <div class="form-group">
                        <label for="name" class="form-label">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" autocomplete="name" required>
                        @error('name')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" autocomplete="email" required>
                        @error('email')
                            <span class="form-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <span class="form-label" id="role-label">Role</span>
                        <input type="text" value="{{ $user->role }}" class="form-control" aria-labelledby="role-label" readonly>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </section>

            <section class="account-section account-actions" aria-labelledby="security-heading">
                <div>
                    <h2 id="security-heading">Security</h2>
                    <p>Keep your account secure with a strong password.</p>
                </div>
                <a href="{{ route('password.edit') }}" class="btn btn-secondary">Change Password</a>
            </section>
        </div>
    </div>
@endsection
