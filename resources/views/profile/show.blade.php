@extends('layouts.app')

@section('title', 'Profile | zYx comic')

@section('content')
    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
        $hasProfilePhoto = $user->avatar_path && Storage::disk('public')->exists($user->avatar_path);
    @endphp

    <div class="profile-shell">
        <div class="profile-container">
            <header class="profile-identity">
                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="profile-avatar-form">
                    @csrf
                    <div class="profile-avatar-stage">
                        <img
                            src="{{ $hasProfilePhoto ? Storage::disk('public')->url($user->avatar_path) : '' }}"
                            alt="{{ $user->name }} profile photo"
                            class="profile-avatar profile-avatar-photo"
                            data-profile-photo-preview
                            @if (! $hasProfilePhoto) hidden @endif
                        >
                        <div class="profile-avatar" aria-hidden="true" data-profile-photo-fallback @if ($hasProfilePhoto) hidden @endif>{{ $initials ?: 'ZY' }}</div>
                        <label for="avatar" class="profile-avatar-edit" aria-label="Choose a new profile photo" title="Choose a new profile photo">
                            <span aria-hidden="true">+</span>
                        </label>
                        <input
                            id="avatar"
                            type="file"
                            name="avatar"
                            accept="image/jpeg,image/png,image/webp"
                            class="profile-avatar-input"
                            data-profile-photo-input
                            required
                        >
                    </div>
                    <p class="profile-photo-help">Choose a photo from your gallery</p>
                    <p class="profile-photo-status" data-profile-photo-status aria-live="polite">JPG, PNG, or WEBP · maximum 2 MB</p>
                    @error('avatar')
                        <span class="form-error profile-photo-error" role="alert">{{ $message }}</span>
                    @enderror
                    <button type="submit" class="btn btn-secondary profile-photo-submit" data-profile-photo-submit>Save Photo</button>
                </form>
                <p class="eyebrow">Your account</p>
                <h1>{{ $user->name }}</h1>
                <div class="profile-badges" aria-label="Account information">
                    <span>{{ $user->role === 'admin' ? 'Administrator' : 'Member' }}</span>
                    <span>{{ $user->hasVerifiedEmail() ? 'Email verified' : 'Email unverified' }}</span>
                    <span>UID: {{ str_pad((string) $user->id, 6, '0', STR_PAD_LEFT) }}</span>
                    <span>Joined {{ $user->created_at->format('M Y') }}</span>
                </div>
            </header>

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

            @unless ($user->hasVerifiedEmail())
                <section class="profile-verification-card" aria-labelledby="verify-email-heading">
                    <div>
                        <p class="eyebrow">Account security</p>
                        <h2 id="verify-email-heading">Verify your email address</h2>
                        <p>Verification is required before you can comment, rate comics, save bookmarks, or use reading history.</p>
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Send Verification Email</button>
                    </form>
                </section>
            @endunless

            <section class="profile-progress-card" aria-labelledby="reading-progress-heading">
                <div class="profile-card-heading">
                    <span class="profile-card-icon" aria-hidden="true">↗</span>
                    <div>
                        <p class="eyebrow">Keep exploring</p>
                        <h2 id="reading-progress-heading">Your comic journey</h2>
                    </div>
                    <strong class="profile-progress-value">{{ $libraryProgress }}%</strong>
                </div>
                <div class="profile-progress-label">
                    <span>Library progress</span>
                    <span>{{ $exploredComicsCount }} of {{ $publishedComicsCount }} comics</span>
                </div>
                <div class="profile-progress-track" role="progressbar" aria-valuenow="{{ $libraryProgress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Library progress">
                    <span style="width: {{ $libraryProgress }}%"></span>
                </div>
                <a href="{{ route('comics') }}" class="profile-text-link">Explore more comics <span aria-hidden="true">→</span></a>
            </section>

            <section class="profile-dashboard-card" aria-labelledby="reading-dashboard-heading">
                <div class="profile-card-heading">
                    <span class="profile-card-icon profile-card-icon-accent" aria-hidden="true">◆</span>
                    <div>
                        <p class="eyebrow">Personal library</p>
                        <h2 id="reading-dashboard-heading">Reading dashboard</h2>
                    </div>
                </div>

                <div class="profile-stat-grid">
                    <div class="profile-stat">
                        <strong>{{ $user->bookmarks_count }}</strong>
                        <span>Bookmarks</span>
                    </div>
                    <div class="profile-stat">
                        <strong>{{ $user->reading_histories_count }}</strong>
                        <span>Reading sessions</span>
                    </div>
                    <div class="profile-stat">
                        <strong>{{ $user->comments_count }}</strong>
                        <span>Comments</span>
                    </div>
                    <div class="profile-stat">
                        <strong>{{ $user->ratings_count }}</strong>
                        <span>Ratings</span>
                    </div>
                </div>

                <nav class="profile-link-list" aria-label="Account shortcuts">
                    <a href="{{ route('bookmarks.index') }}">
                        <span class="profile-link-icon" aria-hidden="true">★</span>
                        <span><strong>My Bookmarks</strong><small>See your saved comics</small></span>
                        <span class="profile-link-arrow" aria-hidden="true">›</span>
                    </a>
                    <a href="{{ route('history.index') }}">
                        <span class="profile-link-icon" aria-hidden="true">↺</span>
                        <span><strong>Reading History</strong><small>Continue where you left off</small></span>
                        <span class="profile-link-arrow" aria-hidden="true">›</span>
                    </a>
                    <a href="{{ route('password.edit') }}">
                        <span class="profile-link-icon" aria-hidden="true">●</span>
                        <span><strong>Account Security</strong><small>Update your password</small></span>
                        <span class="profile-link-arrow" aria-hidden="true">›</span>
                    </a>
                </nav>
            </section>

            <section class="profile-settings-card" aria-labelledby="profile-information-heading">
                <div class="profile-settings-heading">
                    <div>
                        <p class="eyebrow">Account settings</p>
                        <h2 id="profile-information-heading">Profile information</h2>
                    </div>
                    <p>Keep your personal details up to date.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="auth-form profile-form">
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
                        <input type="text" value="{{ $user->role === 'admin' ? 'Administrator' : 'Member' }}" class="form-control" aria-labelledby="role-label" readonly>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </section>
        </div>
    </div>
@endsection
