@extends('layouts.app')

@section('title', 'Verify Email | zYx comic')

@section('content')
    <div class="auth-shell">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <p class="eyebrow">One final step</p>
                <h1>Verify your email</h1>
                <p>We sent a verification link to <strong>{{ $user->email }}</strong>. Open that link to activate community and library features.</p>
            </div>

            @if (session('status') === 'verification-link-sent')
                <div class="form-success" role="status">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <div class="verification-actions">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Resend Verification Email</button>
                </form>

                <a href="{{ route('profile') }}" class="btn btn-ghost">Review Profile</a>
            </div>

            <p class="auth-footer">You can still browse and read public comics while your email is unverified.</p>
        </div>
    </div>
@endsection
