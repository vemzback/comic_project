<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,all'],
        ]);

        $status = $validated['status'] ?? 'pending';

        $comments = Comment::query()
            ->with(['user', 'comic'])
            ->when($status === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true))
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.comments.index', compact('comments', 'status'));
    }

    public function updateApproval(Request $request, Comment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'is_approved' => ['required', 'string', 'in:0,1'],
        ]);

        $comment->update([
            'is_approved' => $validated['is_approved'] === '1',
        ]);

        return redirect()->route('admin.comments.index')->with('success', 'Comment approval updated successfully.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()->route('admin.comments.index')->with('success', 'Comment deleted successfully.');
    }
}
