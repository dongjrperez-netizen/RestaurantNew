<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * Show the unified login page for all user types.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'unified' => true, // Flag to indicate this supports unified login
        ]);
    }

    /**
     * Handle unified authentication request for all user types.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        \Log::info('LOGIN ATTEMPT', ['email' => $request->email]);

        // Check if employee exists
        $employee = \App\Models\Employee::where('email', $request->email)->first();
        \Log::info('EMPLOYEE CHECK', ['found' => $employee ? 'yes' : 'no']);

        $employeeAttempt = Auth::guard('employee')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'));
        \Log::info('EMPLOYEE AUTH ATTEMPT', ['success' => $employeeAttempt ? 'yes' : 'no']);

        if ($employeeAttempt) {
            $request->session()->regenerate();
            \Log::info('EMPLOYEE LOGIN SUCCESS');
            return redirect()->route('dashboard');
        }

        $user = \App\Models\User::where('email', $request->email)->first();
        \Log::info('USER CHECK', ['found' => $user ? 'yes' : 'no']);

        $userAttempt = Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'));
        \Log::info('USER AUTH ATTEMPT', ['success' => $userAttempt ? 'yes' : 'no']);

        if ($userAttempt) {
            $request->session()->regenerate();
            \Log::info('USER LOGIN SUCCESS');
            return redirect()->route('dashboard');
        }

        \Log::info('BOTH LOGIN ATTEMPTS FAILED');
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Destroy an authenticated session for any user type.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Determine which guard is currently authenticated and logout
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        } elseif (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
