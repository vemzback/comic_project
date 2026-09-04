@extends('layouts.app')

@section('title', 'Login | zYx comic')

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-header">
                <p class="eyebrow">Welcome back</p>
                <h1>Sign in to continue reading</h1>
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

            <a href="{{ route('auth.google.redirect') }}" class="btn auth-google-button">
                <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="#4285f4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z"/>
                    <path fill="#34a853" d="M12 22c2.7 0 4.98-.9 6.63-2.36l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"/>
                    <path fill="#fbbc05" d="M6.39 13.93A6 6 0 0 1 6.07 12c0-.67.12-1.32.32-1.93V7.45H3.04A10 10 0 0 0 2 12c0 1.61.39 3.14 1.04 4.55l3.35-2.62Z"/>
                    <path fill="#ea4335" d="M12 5.94c1.47 0 2.79.51 3.83 1.5l2.87-2.88A9.62 9.62 0 0 0 12 2a10 10 0 0 0-8.96 5.45l3.35 2.62C7.18 7.7 9.39 5.94 12 5.94Z"/>
                </svg>
                Continue with Google
            </a>

            <div class="auth-divider"><span>or use email / phone</span></div>

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="login" class="form-label">Email or phone number</label>
                    <input id="login" type="text" name="login" value="{{ old('login', old('email')) }}" class="form-control" autocomplete="username" required autofocus placeholder="name@example.com or 0812...">
                    @error('login')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                    @error('email')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
                    @error('password')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="auth-help-row">
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Sign In</button>
            </form>

            <p class="auth-footer">
                Need an account?
                <a href="{{ route('register') }}">Register</a>
            </p>
        </div>
    </div>
@endsection
