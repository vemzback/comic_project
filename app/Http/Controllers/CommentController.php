<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function index(Comic $comic): Response
    {
        if (! $comic->published_at || $comic->published_at->isFuture()) {
            abort(404);
        }

        $comments = $comic->comments()
            ->whereNull('parent_id')
            ->where('is_approved', true)
            ->with([
                'user',
                'replies' => fn ($replyQuery) => $replyQuery
                    ->where('is_approved', true)
                    ->with('user')
                    ->oldest(),
            ])
            ->latest()
            ->get();

        return response()
            ->view('public.partials.comment-list', compact('comic', 'comments'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Comic $comic): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $parentComment = null;

        if (! empty($validated['parent_id'])) {
            $parentComment = Comment::query()
                ->whereKey($validated['parent_id'])
                ->where('comic_id', $comic->id)
                ->whereNull('parent_id')
                ->where('is_approved', true)
                ->first();

            if (! $parentComment) {
                throw ValidationException::withMessages([
                    'parent_id' => 'The selected comment cannot be replied to.',
                ]);
            }
        }

        $comic->comments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $parentComment?->id,
            'body' => $validated['body'],
            'is_approved' => true,
        ]);

        $message = $parentComment ? 'Reply posted successfully.' : 'Comment posted successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()->route('comic.detail', $comic)->with('success', $message);
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse|JsonResponse
    {
        if ($comment->user_id !== auth()->id()) {
            abort(403, 'You do not own this comment.');
        }

        $comic = $comment->comic;
        $comment->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Comment deleted.']);
        }

        return redirect()->route('comic.detail', $comic)->with('success', 'Comment deleted.');
    }
}
