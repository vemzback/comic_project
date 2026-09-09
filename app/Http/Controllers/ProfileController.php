<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $user->loadCount(['bookmarks', 'comments', 'ratings', 'readingHistories']);

        $publishedComicsCount = Comic::published()->count();
        $exploredComicsCount = $user->readingHistories()
            ->distinct()
            ->count('comic_id');
        $libraryProgress = $publishedComicsCount > 0
            ? min(100, (int) round(($exploredComicsCount / $publishedComicsCount) * 100))
            : 0;

        return view('profile.show', [
            'user' => $user,
            'publishedComicsCount' => $publishedComicsCount,
            'exploredComicsCount' => $exploredComicsCount,
            'libraryProgress' => $libraryProgress,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => PhoneNumber::normalize($request->input('phone')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/', 'unique:users,phone,'.$user->id],
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        $user->save();

        if ($emailChanged) {
            $verificationStatus = 'verification-link-sent';

            try {
                $user->sendEmailVerificationNotification();
            } catch (Throwable $exception) {
                report($exception);
                $verificationStatus = 'verification-link-failed';
            }

            return redirect()->route('verification.notice')
                ->with('status', $verificationStatus);
        }

        return redirect()->route('profile')->with('status', 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $uploadedAvatar = $request->file('avatar');
        $avatarPath = $uploadedAvatar->store('profile-photos', 'public');

        if (! is_string($avatarPath) || $avatarPath === '') {
            throw ValidationException::withMessages([
                'avatar' => ['The profile photo could not be saved. Please try again.'],
            ]);
        }

        $user = Auth::user();
        $previousAvatarPath = $user->avatar_path;

        try {
            $user->forceFill(['avatar_path' => $avatarPath])->save();
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($avatarPath);

            throw $exception;
        }

        if (
            is_string($previousAvatarPath)
            && str_starts_with($previousAvatarPath, 'profile-photos/')
            && $previousAvatarPath !== $avatarPath
        ) {
            Storage::disk('public')->delete($previousAvatarPath);
        }

        return redirect()->route('profile')->with('status', 'Profile photo updated successfully.');
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
        if (! Hash::check($validated['current_password'], $user->password)) {
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
