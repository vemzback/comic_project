<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));
        $role = trim($request->query('role', ''));

        // Validate and filter invalid role values
        $validRoles = ['user', 'admin'];
        if ($role && !in_array($role, $validRoles)) {
            $role = '';
        }

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', function ($q) use ($role) {
                $q->where('role', $role);
            })
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'role'));
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
