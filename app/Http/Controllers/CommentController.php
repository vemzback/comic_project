<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Comic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Comic $comic): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $comic->comments()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'is_approved' => false,
        ]);

        return redirect()->route('comic.detail', $comic)->with('success', 'Comment posted successfully.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        if ($comment->user_id !== auth()->id()) {
            abort(403, 'You do not own this comment.');
        }

        $comic = $comment->comic;
        $comment->delete();

        return redirect()->route('comic.detail', $comic)->with('success', 'Comment deleted.');
    }
}
