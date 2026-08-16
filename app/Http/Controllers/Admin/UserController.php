<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        // Load activity counts (using loadCount for efficient querying)
        $user->loadCount([
            'bookmarks',
            'readingHistories',
            'comments',
            'ratings',
        ]);

        return view('admin.users.show', compact('user'));
    }
}
