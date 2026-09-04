<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => PhoneNumber::normalize($request->input('phone')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'user';

        $user = User::create($validated);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse|Response
    {
        $usesLegacyEmailField = ! $request->has('login') && $request->has('email');
        $login = trim((string) $request->input('login', $request->input('email')));

        $request->merge(['login' => $login]);

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
            $identifier = strtolower($login);
        } else {
            $field = 'phone';
            $identifier = PhoneNumber::normalize($login);

            if (! PhoneNumber::isValid($identifier)) {
                throw ValidationException::withMessages([
                    $usesLegacyEmailField ? 'email' : 'login' => 'Enter a valid email address or phone number.',
                ]);
            }
        }

        $throttleKey = strtolower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response('Too many login attempts.', 429);
        }

        if (Auth::attempt([
            $field => $identifier,
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user && ! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->intended($user && $user->role === 'admin' ? '/admin/dashboard' : '/');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            $usesLegacyEmailField ? 'email' : 'login' => 'These credentials do not match our records.',
        ])->onlyInput($usesLegacyEmailField ? 'email' : 'login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
