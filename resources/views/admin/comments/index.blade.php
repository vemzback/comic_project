@extends('layouts.app')

@section('title', 'Comment Moderation | Admin')

@section('content')
    <div class="admin-page container">
        <div class="admin-page-header">
            <div>
                <p class="eyebrow">Admin Area / Comments</p>
                <h1>Comment Moderation</h1>
                <p>Review user-submitted comments and manage their visibility.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        @if (session('success'))
            <div class="form-success" role="status">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.comments.index') }}" class="admin-card admin-filter-form">
            <div class="admin-field">
                <label for="status" class="form-label">Comment Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="all" @selected($status === 'all')>All</option>
                </select>
            </div>
            <div class="admin-form-actions admin-filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </form>

        @if ($comments->isEmpty())
            <section class="admin-card admin-empty-state">
                @if ($status === 'pending')
                    <h2>No pending comments</h2>
                    <p>New comments awaiting review will appear here.</p>
                @elseif ($status === 'approved')
                    <h2>No approved comments</h2>
                    <p>Approved comments will appear here.</p>
                @else
                    <h2>No comments found</h2>
                    <p>Submitted comments will appear here.</p>
                @endif
            </section>
        @else
            <section class="admin-card admin-table-card" aria-labelledby="comment-catalog-heading">
                <div class="admin-card-header">
                    <h2 id="comment-catalog-heading">Moderation Queue</h2>
                    <p>{{ $comments->total() }} {{ $comments->total() === 1 ? 'comment' : 'comments' }} total</p>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-comment-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Author</th>
                                <th scope="col">Comic</th>
                                <th scope="col">Comment</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comments as $comment)
                                <tr>
                                    <td data-label="ID">{{ $comment->id }}</td>
                                    <td data-label="Author">
                                        <strong>{{ $comment->user->name }}</strong>
                                        <small class="admin-table-meta">{{ $comment->user->email }}</small>
                                    </td>
                                    <td data-label="Comic" class="admin-table-title">{{ $comment->comic->title }}</td>
                                    <td data-label="Comment" class="admin-comment-body">{{ \Illuminate\Support\Str::limit($comment->body, 120) }}</td>
                                    <td data-label="Status">
                                        <span class="status-badge {{ $comment->is_approved ? 'status-approved' : 'status-pending' }}">
                                            {{ $comment->is_approved ? 'Approved' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td data-label="Submitted">{{ $comment->created_at->format('Y-m-d H:i') }}</td>
                                    <td data-label="Actions">
                                        <div class="admin-actions">
                                            <form method="POST" action="{{ route('admin.comments.approval.update', $comment) }}" class="inline-form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_approved" value="{{ $comment->is_approved ? '0' : '1' }}">
                                                <button type="submit" class="btn btn-secondary">{{ $comment->is_approved ? 'Unapprove' : 'Approve' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}" class="inline-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="admin-pagination">{{ $comments->links() }}</div>
        @endif
    </div>
@endsection
