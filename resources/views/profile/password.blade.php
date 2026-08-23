@extends('layouts.app')

@section('title', 'Change Password | Comic Project')

@section('content')
    <div class="account-shell">
        <div class="account-card account-card-narrow">
            <div class="account-header">
                <p class="eyebrow">Account security</p>
                <h1>Change Password</h1>
                <p>Keep your account secure</p>
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

            <form method="POST" action="{{ route('password.update') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input id="current_password" type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                    @error('current_password')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">New Password</label>
                    <input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required>
                    @error('password')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                    @error('password_confirmation')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>

            <a href="{{ route('profile') }}" class="account-back-link">Back to Profile</a>
        </div>
    </div>
@endsection
