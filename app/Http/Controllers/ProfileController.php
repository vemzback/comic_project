<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();

        return view('profile.show', ['user' => $user]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->save();

        return redirect()->route('profile')->with('status', 'Profile updated successfully.');
    }

    public function showPassword(): View
    {
        $user = Auth::user();

        return view('profile.password', ['user' => $user]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        // Update password
        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ]);

        $user->save();

        return redirect()->route('profile')->with('status', 'Password changed successfully.');
    }
}
