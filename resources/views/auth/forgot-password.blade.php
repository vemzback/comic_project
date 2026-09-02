@extends('layouts.app')

@section('title', 'Forgot Password | zYx comic')

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-header">
                <p class="eyebrow">Account recovery</p>
                <h1>Reset your password</h1>
                <p>Enter your account email and we will send you a secure reset link.</p>
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

            <form method="POST" action="{{ route('password.email') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="email" required autofocus>
                    @error('email')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Send Reset Link</button>
            </form>

            <p class="auth-footer">
                Remember your password?
                <a href="{{ route('login') }}">Back to login</a>
            </p>
        </div>
    </div>
@endsection
