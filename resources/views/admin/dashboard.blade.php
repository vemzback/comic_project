@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="container" style="padding: 2rem 0 3rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:2rem; flex-wrap:wrap;">
            <div>
                <p style="margin:0; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em; font-size:0.7rem; font-weight:700;">Admin Area</p>
                <h1 style="margin:0.25rem 0 0; font-size:2.2rem;">Platform Overview</h1>
            </div>

            <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
                <a href="{{ route('home') }}" class="btn btn-ghost">View Site</a>
                <a href="{{ route('admin.comics.index') }}" class="btn btn-primary">Manage Comics</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:2rem;">
            @foreach ($metrics as $metric)
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1.25rem; box-shadow:0 8px 24px rgba(15, 23, 42, 0.04);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <span style="font-size:1.8rem;">{{ $metric['icon'] }}</span>
                        <span style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; font-weight:700;">Live</span>
                    </div>
                    <div style="font-size:2rem; font-weight:800; line-height:1.1;">{{ number_format($metric['value']) }}</div>
                    <div style="margin-top:0.3rem; color:#4b5563; font-weight:600;">{{ $metric['label'] }}</div>
                </div>
            @endforeach
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem;">
            <section style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h2 style="margin:0; font-size:1.2rem;">Recent Users</h2>
                    <a href="{{ route('admin.users.index') }}" style="color:#1d4ed8; text-decoration:none;">View all</a>
                </div>

                @if ($recentUsers->isEmpty())
                    <p style="margin:0; color:#6b7280;">No users yet.</p>
                @else
                    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.8rem;">
                        @foreach ($recentUsers as $user)
                            <li style="display:flex; justify-content:space-between; gap:0.75rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.6rem;">
                                <div>
                                    <div style="font-weight:700;">{{ $user->name }}</div>
                                    <div style="font-size:0.85rem; color:#6b7280;">{{ $user->email }}</div>
                                </div>
                                <span style="font-size:0.75rem; color:#6b7280; text-transform:capitalize;">{{ $user->role }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h2 style="margin:0; font-size:1.2rem;">Recent Comics</h2>
                    <a href="{{ route('admin.comics.index') }}" style="color:#1d4ed8; text-decoration:none;">Open catalog</a>
                </div>

                @if ($recentComics->isEmpty())
                    <p style="margin:0; color:#6b7280;">No comics yet.</p>
                @else
                    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.8rem;">
                        @foreach ($recentComics as $comic)
                            <li style="display:flex; justify-content:space-between; gap:0.75rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.6rem;">
                                <div>
                                    <div style="font-weight:700;">{{ $comic->title }}</div>
                                    <div style="font-size:0.85rem; color:#6b7280;">{{ ucfirst($comic->status) }}</div>
                                </div>
                                <span style="font-size:0.75rem; color:#6b7280;">{{ $comic->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h2 style="margin:0; font-size:1.2rem;">Recent Chapters</h2>
                    <a href="{{ route('admin.comics.index') }}" style="color:#1d4ed8; text-decoration:none;">Manage</a>
                </div>

                @if ($recentChapters->isEmpty())
                    <p style="margin:0; color:#6b7280;">No chapters yet.</p>
                @else
                    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.8rem;">
                        @foreach ($recentChapters as $chapter)
                            <li style="display:flex; justify-content:space-between; gap:0.75rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.6rem;">
                                <div>
                                    <div style="font-weight:700;">{{ $chapter->title ?: 'Chapter ' . $chapter->chapter_number }}</div>
                                    <div style="font-size:0.85rem; color:#6b7280;">{{ $chapter->comic?->title ?? 'Unknown comic' }}</div>
                                </div>
                                <span style="font-size:0.75rem; color:#6b7280;">{{ $chapter->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h2 style="margin:0; font-size:1.2rem;">Recent Comments</h2>
                    <a href="{{ route('admin.comments.index') }}" style="color:#1d4ed8; text-decoration:none;">Moderate</a>
                </div>

                @if ($recentComments->isEmpty())
                    <p style="margin:0; color:#6b7280;">No comments yet.</p>
                @else
                    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.8rem;">
                        @foreach ($recentComments as $comment)
                            <li style="border-bottom:1px solid #f3f4f6; padding-bottom:0.6rem;">
                                <div style="font-weight:700;">{{ $comment->user?->name ?? 'Unknown user' }}</div>
                                <div style="font-size:0.85rem; color:#6b7280; margin:0.2rem 0;">{{ $comment->comic?->title ?? 'Unknown comic' }}</div>
                                <div style="color:#374151;">{{ \Illuminate\Support\Str::limit($comment->body, 80) }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h2 style="margin:0; font-size:1.2rem;">Recent Ratings</h2>
                </div>

                @if ($recentRatings->isEmpty())
                    <p style="margin:0; color:#6b7280;">No ratings yet.</p>
                @else
                    <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.8rem;">
                        @foreach ($recentRatings as $rating)
                            <li style="display:flex; justify-content:space-between; gap:0.75rem; border-bottom:1px solid #f3f4f6; padding-bottom:0.6rem;">
                                <div>
                                    <div style="font-weight:700;">{{ $rating->user?->name ?? 'Unknown user' }}</div>
                                    <div style="font-size:0.85rem; color:#6b7280;">{{ $rating->comic?->title ?? 'Unknown comic' }}</div>
                                </div>
                                <span style="font-weight:700; color:#f59e0b;">{{ $rating->score }}/5</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
