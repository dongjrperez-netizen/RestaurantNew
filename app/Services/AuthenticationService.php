<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationService
{
    /**
     * Check if user account status allows login
     */
    public function validateUserStatus(User $user): ?array
    {
        return match ($user->status) {
            'Pending' => [
                'status' => 'Your account is pending approval. Please wait for administrator approval.'
            ],
            'Rejected' => null, // Allow login but redirect to account update
            default => null, // No errors
        };
    }

    /**
     * Check if employee account is active
     */
    public function validateEmployeeStatus(Employee $employee): ?array
    {
        if ($employee->status !== 'active') {
            return [
                'email' => 'Your employee account is not active. Please contact your manager.'
            ];
        }

        return null;
    }

    /**
     * Check if restaurant has active subscription through employee
     */
    public function validateEmployeeSubscription(Employee $employee): ?array
    {
        $restaurant = $employee->restaurant;

        if (!$restaurant) {
            return [
                'email' => 'No restaurant associated with this employee account.'
            ];
        }

        $subscription = $restaurant->subscription()
            ->orderByDesc('subscription_endDate')
            ->first();

        if (!$subscription || strtolower($subscription->subscription_status) !== 'active') {
            return [
                'email' => 'Restaurant subscription is not active. Please contact your manager.'
            ];
        }

        return null;
    }

    /**
     * Check user subscription and determine redirect
     */
    public function handleUserSubscriptionRedirect(User $user): RedirectResponse
    {
        $subscription = $user->subscription()
            ->orderByDesc('subscription_endDate')
            ->first();

        if ($subscription && strtolower($subscription->subscription_status) === 'active') {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($subscription && strtolower($subscription->subscription_status) === 'archive') {
            return redirect()->intended(route('subscriptions.renew', absolute: false));
        } else {
            return redirect()->intended(route('subscriptions.index', absolute: false));
        }
    }

    /**
     * Handle employee role-based redirect
     */
    public function handleEmployeeRoleRedirect(Employee $employee): RedirectResponse
    {
        // DEBUG: Log employee redirect attempt
        \Log::info('🔍 EMPLOYEE REDIRECT START', [
            'employee_id' => $employee->employee_id,
            'email' => $employee->email,
            'role_id_raw' => $employee->getAttribute('role_id'), // Get raw value
        ]);

        // Get the role - it's already cast as UserRole enum in the model
        $role = $employee->role_id;

        // DEBUG: Log role information
        \Log::info('🔍 EMPLOYEE ROLE INFO', [
            'role_type' => get_class($role),
            'role_value' => $role->value,
            'role_label' => $role->label(),
            'is_user_role' => $role instanceof UserRole,
            'is_waiter' => $role->isWaiter(),
        ]);

        if (!$role instanceof UserRole) {
            \Log::warning('🔍 ROLE NOT INSTANCE OF UserRole - DEFAULT REDIRECT');
            return redirect()->route('dashboard');
        }

        // Special handling for waiters - check for active menu plan
        if ($role->isWaiter()) {
            \Log::info('🔍 WAITER DETECTED - CALLING WAITER REDIRECT');
            return $this->handleWaiterRedirect($employee);
        }

        // For managers, supervisors, restaurant owners
        $redirectRoute = $role->redirectRoute();
        \Log::info('🔍 NON-WAITER REDIRECT', [
            'redirect_route' => $redirectRoute
        ]);
        return redirect()->route($redirectRoute);
    }

    /**
     * Handle waiter-specific redirect logic
     */
    private function handleWaiterRedirect(Employee $employee): RedirectResponse
    {
        \Log::info('🔍 WAITER REDIRECT START');

        $restaurant = $employee->restaurant;

        if (!$restaurant) {
            \Log::warning('🔍 NO RESTAURANT FOUND - DEFAULT REDIRECT');
            return redirect()->route('dashboard');
        }

        \Log::info('🔍 RESTAURANT FOUND', [
            'restaurant_id' => $restaurant->id,
            'restaurant_email' => $restaurant->email
        ]);

        $todayMenuPlan = $restaurant->menuPlans()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        \Log::info('🔍 MENU PLAN SEARCH', [
            'menu_plan_found' => $todayMenuPlan ? 'Yes' : 'No',
            'menu_plan_id' => $todayMenuPlan->menu_plan_id ?? null,
            'today_date' => now()->format('Y-m-d')
        ]);

        if ($todayMenuPlan) {
            $redirectUrl = route('menu-planning.mobile-view', [
                'menuPlan' => $todayMenuPlan->menu_plan_id,
                'date' => now()->format('Y-m-d')
            ]);

            \Log::info('🔍 WAITER REDIRECT TO MOBILE VIEW', [
                'redirect_url' => $redirectUrl,
                'menu_plan_id' => $todayMenuPlan->menu_plan_id
            ]);

            return redirect()->route('menu-planning.mobile-view', [
                'menuPlan' => $todayMenuPlan->menu_plan_id,
                'date' => now()->format('Y-m-d'),
                'test' => 'redirect'  // Add test parameter for verification
            ]);
        }

        \Log::info('🔍 NO ACTIVE MENU PLAN - REDIRECT TO INDEX');
        return redirect()->route('menu-planning.index.employee')
            ->with('warning', 'No active menu plan found for today.');
    }

    /**
     * Handle rate limiting for authentication requests
     */
    public function handleRateLimit(string $throttleKey): void
    {
        RateLimiter::hit($throttleKey);
    }

    /**
     * Clear rate limiting for successful authentication
     */
    public function clearRateLimit(string $throttleKey): void
    {
        RateLimiter::clear($throttleKey);
    }

    /**
     * Detect user type by email and return array with user info
     */
    public function detectUserType(string $email): array
    {
        // Check if it's a restaurant owner (User)
        $user = User::where('email', $email)->first();
        if ($user) {
            return [
                'type' => 'user',
                'model' => $user,
                'guard' => 'web'
            ];
        }

        // Check if it's an employee
        $employee = Employee::where('email', $email)->first();
        if ($employee) {
            return [
                'type' => 'employee',
                'model' => $employee,
                'guard' => 'employee'
            ];
        }

        return [
            'type' => null,
            'model' => null,
            'guard' => null
        ];
    }

    /**
     * Authenticate user with appropriate guard and perform validations
     */
    public function attemptUnifiedLogin(string $email, string $password, bool $remember = false): array
    {
        // DEBUG: Log the detection process
        \Log::info('🔍 USER TYPE DETECTION', [
            'email' => $email,
            'password_length' => strlen($password)
        ]);

        $userInfo = $this->detectUserType($email);

        // DEBUG: Log the detection result
        \Log::info('🔍 USER TYPE DETECTED', [
            'type' => $userInfo['type'],
            'guard' => $userInfo['guard'],
            'model_found' => $userInfo['model'] ? 'Yes' : 'No'
        ]);

        if (!$userInfo['model']) {
            return [
                'success' => false,
                'errors' => ['email' => 'No account found with this email address.'],
                'user_type' => null
            ];
        }

        // Verify password
        $passwordCheck = Hash::check($password, $userInfo['model']->password);

        // DEBUG: Log password verification
        \Log::info('🔍 PASSWORD VERIFICATION', [
            'email' => $email,
            'password_provided' => $password,
            'password_hash' => $userInfo['model']->password,
            'password_check_result' => $passwordCheck ? 'MATCH' : 'NO MATCH'
        ]);

        if (!$passwordCheck) {
            return [
                'success' => false,
                'errors' => ['email' => 'The provided credentials do not match our records.'],
                'user_type' => $userInfo['type']
            ];
        }

        // Perform type-specific validations
        if ($userInfo['type'] === 'user') {
            $statusErrors = $this->validateUserStatus($userInfo['model']);
            if ($statusErrors) {
                // Allow login for 'Rejected' status but will redirect to account update
                if ($userInfo['model']->status !== 'Rejected') {
                    return [
                        'success' => false,
                        'errors' => $statusErrors,
                        'user_type' => 'user'
                    ];
                }
            }
        } elseif ($userInfo['type'] === 'employee') {
            $statusErrors = $this->validateEmployeeStatus($userInfo['model']);
            if ($statusErrors) {
                return [
                    'success' => false,
                    'errors' => $statusErrors,
                    'user_type' => 'employee'
                ];
            }

            $subscriptionErrors = $this->validateEmployeeSubscription($userInfo['model']);
            if ($subscriptionErrors) {
                return [
                    'success' => false,
                    'errors' => $subscriptionErrors,
                    'user_type' => 'employee'
                ];
            }
        }

        // Attempt authentication with appropriate guard
        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        if (Auth::guard($userInfo['guard'])->attempt($credentials, $remember)) {
            return [
                'success' => true,
                'user_type' => $userInfo['type'],
                'guard' => $userInfo['guard'],
                'model' => $userInfo['model']
            ];
        }

        return [
            'success' => false,
            'errors' => ['email' => 'Authentication failed. Please try again.'],
            'user_type' => $userInfo['type']
        ];
    }

    /**
     * Handle unified login redirect based on user type
     */
    public function handleUnifiedLoginRedirect(string $userType, $userModel): RedirectResponse
    {
        if ($userType === 'user') {
            // Handle rejected status - redirect to account update
            if ($userModel->status === 'Rejected') {
                return redirect()->intended(route('account.update', absolute: false));
            }

            return $this->handleUserSubscriptionRedirect($userModel);
        }

        if ($userType === 'employee') {
            return $this->handleEmployeeRoleRedirect($userModel);
        }

        // Fallback
        return redirect()->route('dashboard');
    }
}