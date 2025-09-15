<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {

        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if ($user) {
            // Handle status checks
            if ($user->status === 'Pending') {
                return back()->withErrors([
                    'status' => 'Your account is pending approval. Please wait for administrator approval.',
                ]);
            }

            if ($user->status === 'Rejected') {
                $request->authenticate();
                $request->session()->regenerate();

                return redirect()->intended(route('account.update', absolute: false));
            }
        }

        $request->authenticate();
        $request->session()->regenerate();

        // Check if user has an active subscription
        $user = auth()->user();
        $subscription = $user->subscription()->orderByDesc('subscription_endDate')->first(); // or ->latest('created_at')->first();

        if ($subscription && strtolower($subscription->subscription_status) === 'active') {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($subscription && strtolower($subscription->subscription_status) === 'archive') {
            return redirect()->intended(route('subscriptions.renew', absolute: false));
        } else {
            return redirect()->intended(route('subscriptions.index', absolute: false));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
