@extends('layouts.app')

@section('title', 'Choose New Password | zYx comic')

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-header">
                <p class="eyebrow">Secure your account</p>
                <h1>Choose a new password</h1>
                <p>Use at least eight characters and avoid reusing an old password.</p>
            </div>

            @if ($errors->any())
                <div class="form-error-box" role="alert">
                    <ul class="form-error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="auth-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="form-control" autocomplete="email" required autofocus>
                    @error('email')
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
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Reset Password</button>
            </form>
        </div>
    </div>
@endsection
