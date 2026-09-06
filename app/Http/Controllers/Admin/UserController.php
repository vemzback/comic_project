<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));
        $role = trim($request->query('role', ''));

        // Validate and filter invalid role values
        $validRoles = ['user', 'admin'];
        if ($role && ! in_array($role, $validRoles)) {
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

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:user,admin'],
        ]);

        if ((int) Auth::id() === (int) $user->id) {
            abort(403);
        }

        if ($user->role === $validated['role']) {
            return redirect()->route('admin.users.index')->with('status', 'User role is unchanged.');
        }

        DB::transaction(function () use ($user, $validated): void {
            if ($user->role === 'admin' && $validated['role'] === 'user') {
                $admins = User::query()
                    ->where('role', 'admin')
                    ->lockForUpdate()
                    ->get();

                if ($admins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'The final administrator cannot be demoted.',
                    ]);
                }
            }

            $user->update([
                'role' => $validated['role'],
            ]);
        });

        return redirect()->route('admin.users.index')->with('status', 'User role updated successfully.');
    }
}
