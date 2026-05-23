<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'requires2fa' => session('requires_2fa', false),
            'email' => session('email', ''),
            'remember' => session('remember', false),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->ensureIsNotRateLimited();

        if (!Auth::validate($request->only('email', 'password'))) {
            RateLimiter::hit($request->throttleKey());
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        // Check if 2FA is needed: 
        // 1. Code is null (never connected)
        // 2. Code exists but is pending (expires_at is NOT null) AND has expired
        if (!$user->code || ($user->code_expires_at && $user->code_expires_at->isPast())) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'code' => $code,
                'code_expires_at' => now()->addMinute(),
            ]);
            try {
                $user->notify(new TwoFactorCodeNotification($code));
            } catch (\Exception $e) {
                \Log::error("Failed to send 2FA notification: " . $e->getMessage());
            }

            RateLimiter::clear($request->throttleKey());

            return back()->with([
                'requires_2fa' => true,
                'email' => $request->email,
                'remember' => $request->boolean('remember'),
            ]);
        }

        // If code exists and is still pending (not expired), show the popup again
        if ($user->code_expires_at && !$user->code_expires_at->isPast()) {
            RateLimiter::clear($request->throttleKey());
            return back()->with([
                'requires_2fa' => true,
                'email' => $request->email,
                'remember' => $request->boolean('remember'),
            ]);
        }

        // If code exists and expires_at is null, it means it was already verified
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        RateLimiter::clear($request->throttleKey());

        if ($user->is_admin) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Verify the 2FA code and log in the user.
     */
    public function verify2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'remember' => 'boolean',
        ]);

        $user = User::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => 'Le code de vérification est incorrect.',
            ]);
        }

        if ($user->code_expires_at && $user->code_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Le code de vérification a expiré (validité 1min). Veuillez vous reconnecter pour en recevoir un nouveau.',
            ]);
        }

        // Mark as verified by clearing expires_at
        $user->update(['code_expires_at' => null]);

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        if ($user->is_admin) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
