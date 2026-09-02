@if ($comments->isEmpty())
    <div class="empty-state">
        <p>No comments</p>
    </div>
@else
    @foreach ($comments as $comment)
        <div class="comment-item" data-comment-id="{{ $comment->id }}">
            <div class="comment-heading">
                @include('public.partials.comment-author', ['author' => $comment->user, 'postedAt' => $comment->created_at])
            </div>
            <p class="comment-body">{{ $comment->body }}</p>

            @auth
                @if ($comment->user_id === auth()->id())
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="comment-delete-form" data-comment-action>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                @endif
            @endauth

            @if ($comment->replies->isNotEmpty())
                <div class="comment-replies" aria-label="Replies to {{ $comment->user->name }}">
                    @foreach ($comment->replies as $reply)
                        <div class="comment-reply" data-comment-id="{{ $reply->id }}">
                            <div class="comment-heading">
                                @include('public.partials.comment-author', ['author' => $reply->user, 'postedAt' => $reply->created_at])
                            </div>
                            <p class="comment-body">{{ $reply->body }}</p>

                            @auth
                                @if ($reply->user_id === auth()->id())
                                    <form method="POST" action="{{ route('comments.destroy', $reply) }}" class="comment-delete-form" data-comment-action>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    @endforeach
                </div>
            @endif

            @auth
                <details class="comment-reply-box" data-reply-for="{{ $comment->id }}" @if ((string) old('parent_id') === (string) $comment->id) open @endif>
                    <summary>Reply</summary>
                    <form method="POST" action="{{ route('comments.store', $comic) }}" class="reply-form" data-comment-action>
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <div class="form-group">
                            <label for="reply-body-{{ $comment->id }}" class="sr-only">Reply to {{ $comment->user->name }}</label>
                            <textarea id="reply-body-{{ $comment->id }}" name="body" rows="3" class="form-control" placeholder="Reply to {{ $comment->user->name }}" required>{{ (string) old('parent_id') === (string) $comment->id ? old('body') : '' }}</textarea>
                        </div>
                        @if ((string) old('parent_id') === (string) $comment->id)
                            @error('body')
                                <div class="alert alert-danger interaction-error">{{ $message }}</div>
                            @enderror
                            @error('parent_id')
                                <div class="alert alert-danger interaction-error">{{ $message }}</div>
                            @enderror
                        @endif
                        <button type="submit" class="btn btn-secondary btn-sm">Post Reply</button>
                    </form>
                </details>
            @endauth
        </div>
    @endforeach
@endif
