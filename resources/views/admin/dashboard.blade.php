@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="container admin-dashboard">
        <div class="admin-dashboard-header">
            <div>
                <p class="eyebrow">Admin Area</p>
                <h1>Platform Overview</h1>
            </div>

            <div class="admin-dashboard-actions">
                <a href="{{ route('home') }}" class="btn btn-ghost">View Site</a>
                <a href="{{ route('admin.comics.index') }}" class="btn btn-primary">Manage Comics</a>
                <a href="{{ route('admin.genres.index') }}" class="btn btn-secondary">Manage Genres</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>

        <div class="admin-metrics-grid">
            @foreach ($metrics as $metric)
                <div class="admin-metric-card">
                    <div class="admin-metric-heading">
                        <span class="admin-metric-icon">{{ $metric['icon'] }}</span>
                        <span class="admin-metric-live">Live</span>
                    </div>
                    <strong class="admin-metric-value">{{ number_format($metric['value']) }}</strong>
                    <span class="admin-metric-label">{{ $metric['label'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="admin-overview-grid">
            <section class="admin-overview-card">
                <div class="admin-overview-heading">
                    <h2>Recent Users</h2>
                    <a href="{{ route('admin.users.index') }}">View all</a>
                </div>

                @if ($recentUsers->isEmpty())
                    <p class="admin-overview-empty">No users yet.</p>
                @else
                    <ul class="admin-overview-list">
                        @foreach ($recentUsers as $user)
                            <li class="admin-overview-item">
                                <div class="admin-overview-copy">
                                    <strong>{{ $user->name }}</strong>
                                    <small>{{ $user->email }}</small>
                                </div>
                                <span class="admin-overview-meta admin-role-label">{{ $user->role }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="admin-overview-card">
                <div class="admin-overview-heading">
                    <h2>Recent Comics</h2>
                    <a href="{{ route('admin.comics.index') }}">Open catalog</a>
                </div>

                @if ($recentComics->isEmpty())
                    <p class="admin-overview-empty">No comics yet.</p>
                @else
                    <ul class="admin-overview-list">
                        @foreach ($recentComics as $comic)
                            <li class="admin-overview-item">
                                <div class="admin-overview-copy">
                                    <strong>{{ $comic->title }}</strong>
                                    <small>{{ ucfirst($comic->status) }}</small>
                                </div>
                                <span class="admin-overview-meta">{{ $comic->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="admin-overview-card">
                <div class="admin-overview-heading">
                    <h2>Recent Chapters</h2>
                    <a href="{{ route('admin.comics.index') }}">Manage</a>
                </div>

                @if ($recentChapters->isEmpty())
                    <p class="admin-overview-empty">No chapters yet.</p>
                @else
                    <ul class="admin-overview-list">
                        @foreach ($recentChapters as $chapter)
                            <li class="admin-overview-item">
                                <div class="admin-overview-copy">
                                    <strong>{{ $chapter->title ?: 'Chapter '.$chapter->chapter_number }}</strong>
                                    <small>{{ $chapter->comic?->title ?? 'Unknown comic' }}</small>
                                </div>
                                <span class="admin-overview-meta">{{ $chapter->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="admin-overview-card">
                <div class="admin-overview-heading">
                    <h2>Recent Comments</h2>
                    <a href="{{ route('admin.comments.index') }}">Moderate</a>
                </div>

                @if ($recentComments->isEmpty())
                    <p class="admin-overview-empty">No comments yet.</p>
                @else
                    <ul class="admin-overview-list">
                        @foreach ($recentComments as $comment)
                            <li class="admin-overview-item admin-comment-overview-item">
                                <strong>{{ $comment->user?->name ?? 'Unknown user' }}</strong>
                                <small>{{ $comment->comic?->title ?? 'Unknown comic' }}</small>
                                <p>{{ \Illuminate\Support\Str::limit($comment->body, 80) }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="admin-overview-card">
                <div class="admin-overview-heading">
                    <h2>Recent Ratings</h2>
                </div>

                @if ($recentRatings->isEmpty())
                    <p class="admin-overview-empty">No ratings yet.</p>
                @else
                    <ul class="admin-overview-list">
                        @foreach ($recentRatings as $rating)
                            <li class="admin-overview-item">
                                <div class="admin-overview-copy">
                                    <strong>{{ $rating->user?->name ?? 'Unknown user' }}</strong>
                                    <small>{{ $rating->comic?->title ?? 'Unknown comic' }}</small>
                                </div>
                                <span class="admin-rating-score">{{ $rating->score }}/5</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
