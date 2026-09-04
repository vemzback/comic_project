<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google login is not configured yet. Please use email or phone number.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google login is not configured yet. Please use email or phone number.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'google' => 'Google login could not be completed. Please try again.',
            ]);
        }

        $googleId = trim((string) $googleUser->getId());
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google did not provide a usable verified email address.',
            ]);
        }

        if (($googleUser->user['verified_email'] ?? true) === false) {
            return redirect()->route('login')->withErrors([
                'google' => 'Your Google email address must be verified before signing in.',
            ]);
        }

        $emailOwner = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($emailOwner && $emailOwner->google_id && $emailOwner->google_id !== $googleId) {
            return redirect()->route('login')->withErrors([
                'google' => 'This email address is already linked to another Google account.',
            ]);
        }

        $user = DB::transaction(function () use ($googleUser, $googleId, $email, $emailOwner): User {
            $user = User::where('google_id', $googleId)->first();

            if (! $user) {
                $user = $emailOwner;
            }

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return $user;
            }

            return User::create([
                'name' => trim((string) $googleUser->getName()) ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(64)),
                'role' => 'user',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($user->role === 'admin' ? '/admin/dashboard' : '/');
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
