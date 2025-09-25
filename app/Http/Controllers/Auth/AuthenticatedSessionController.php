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
        \Log::info('🔍 LOGIN CONTROLLER REACHED', ['email' => $request->email, 'timestamp' => now()]);
        \Log::info('LOGIN ATTEMPT', ['email' => $request->email]);

        // First, try admin authentication
        $admin = \App\Models\Administrator::where('email', $request->email)->first();
        \Log::info('ADMIN CHECK', ['found' => $admin ? 'yes' : 'no']);

        if ($admin && Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            \Log::info('ADMIN LOGIN SUCCESS');
            return redirect()->intended(route('admin.dashboard'));
        }

        // Then, try supplier authentication
        $supplier = \App\Models\Supplier::where('email', $request->email)->first();
        \Log::info('SUPPLIER CHECK', ['found' => $supplier ? 'yes' : 'no']);

        if ($supplier) {
            $attemptResult = Auth::guard('supplier')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'));
            \Log::info('SUPPLIER AUTH ATTEMPT', ['success' => $attemptResult ? 'yes' : 'no']);

            if ($attemptResult) {
                $request->session()->regenerate();
                \Log::info('SUPPLIER LOGIN SUCCESS - REDIRECTING TO DASHBOARD');
                return redirect()->intended(route('supplier.dashboard'));
            }
        }

        // Next, try employee authentication
        $employee = \App\Models\Employee::where('email', $request->email)->first();
        \Log::info('EMPLOYEE CHECK', ['found' => $employee ? 'yes' : 'no']);

        if ($employee && Auth::guard('employee')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            \Log::info('EMPLOYEE LOGIN SUCCESS', ['role_id' => $employee->role_id->value]);

            // Redirect based on employee role
            return $this->redirectEmployeeByRole($employee);
        }

        // Finally, try regular user authentication
        $user = \App\Models\User::where('email', $request->email)->first();
        \Log::info('USER CHECK', ['found' => $user ? 'yes' : 'no']);

        if ($user && Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            \Log::info('USER LOGIN SUCCESS', ['role_id' => $user->role_id]);

            // Restaurant owners always go to main dashboard
            return redirect()->intended(route('dashboard'));
        }

        \Log::info('ALL LOGIN ATTEMPTS FAILED');
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Redirect employee based on their role.
     */
    private function redirectEmployeeByRole(\App\Models\Employee $employee): RedirectResponse
    {
        $userRole = $employee->role_id;

        switch ($userRole) {
            case \App\Enums\UserRole::WAITER:
                // Check if there's an active menu plan for mobile view
                $activeMenuPlan = $employee->restaurant->menuPlans()->where('is_active', true)->first();
                if ($activeMenuPlan) {
                    return redirect()->route('menu-planning.mobile-view', [
                        'menuPlan' => $activeMenuPlan->id,
                        'date' => now()->format('Y-m-d')
                    ]);
                }
                // If no active menu plan, go to menu planning index
                return redirect()->route('menu-planning.index.employee');

            case \App\Enums\UserRole::MANAGER:
            case \App\Enums\UserRole::SUPERVISOR:
            case \App\Enums\UserRole::RESTAURANT_OWNER:
            default:
                return redirect()->intended(route('dashboard'));
        }
    }

    /**
     * Destroy an authenticated session for any user type.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Determine which guard is currently authenticated and logout
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
        } elseif (Auth::guard('supplier')->check()) {
            Auth::guard('supplier')->logout();
        } elseif (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
        } elseif (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
