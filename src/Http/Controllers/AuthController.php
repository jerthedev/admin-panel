<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Authentication Controller
 *
 * Handles admin panel authentication including login, logout,
 * and user profile management.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => config('admin-panel.auth.password_reset', true),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited($request);

        $guard = config('admin-panel.auth.guard', 'admin');
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::guard($guard)->attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Check if user is authorized for admin panel
        $user = Auth::guard($guard)->user();
        if (! $this->isAuthorizedForAdmin($user)) {
            Auth::guard($guard)->logout();

            throw ValidationException::withMessages([
                'email' => 'You are not authorized to access the admin panel.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        return redirect()->intended(route('admin-panel.dashboard'));
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        $guard = config('admin-panel.auth.guard', 'admin');

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin-panel.login');
    }

    /**
     * Show user profile.
     */
    public function profile(Request $request): Response
    {
        return Inertia::render('Auth/Profile', [
            'user' => $request->user(),
            'sessions' => $this->getUserSessions($request),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('name', 'email'));

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }

    /**
     * Check if user is authorized for admin panel.
     */
    protected function isAuthorizedForAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        // Use custom authorization callback
        $callback = config('admin-panel.auth.authorize');
        if ($callback && is_callable($callback)) {
            return call_user_func($callback, $user);
        }

        // Check if all authenticated users are allowed
        if (config('admin-panel.auth.allow_all_authenticated', false)) {
            return true;
        }

        // Default authorization checks
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        }

        if (isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        if (isset($user->admin_access)) {
            return (bool) $user->admin_access;
        }

        if (method_exists($user, 'can')) {
            return $user->can('access-admin-panel');
        }

        return false;
    }

    /**
     * Get user sessions for profile display.
     */
    protected function getUserSessions(Request $request): array
    {
        // This would integrate with session storage to show active sessions
        // For now, return basic session info
        return [
            [
                'id' => session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_active' => now()->toISOString(),
                'is_current' => true,
            ]
        ];
    }
}
