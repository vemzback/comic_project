@extends('layouts.app')

@section('title', 'Register | Comic Project')

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-header">
                <p class="eyebrow">Join the community</p>
                <h1>Create your account</h1>
                <p>Start reading and saving comics</p>
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

            <form method="POST" action="{{ route('register') }}" class="auth-form">
                @csrf

                <div class="form-group">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control" autocomplete="name" required>
                    @error('name')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="email" required>
                    @error('email')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required>
                    @error('password')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                    @error('password_confirmation')
                        <span class="form-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Create Account</button>
            </form>

            <p class="auth-footer">
                Already have an account?
                <a href="{{ route('login') }}">Login</a>
            </p>
        </div>
    </div>
@endsection
