@extends('layouts.app')

@section('title', 'Login | Comic Project')

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-header">
                <p class="eyebrow">Welcome back</p>
                <h1>Sign in to continue reading</h1>
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

            <form method="POST" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="email" required autofocus>
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

                <button type="submit" class="btn btn-primary auth-submit">Sign In</button>
            </form>

            <p class="auth-footer">
                Need an account?
                <a href="{{ route('register') }}">Register</a>
            </p>
        </div>
    </div>
@endsection
