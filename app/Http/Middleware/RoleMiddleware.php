<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated with employee guard
        if (!Auth::guard('employee')->check()) {
            abort(401, 'Unauthorized.');
        }

        $employee = Auth::guard('employee')->user();

        // Check if employee has the required role
        if (!$employee->role || strtolower($employee->role->role_name) !== strtolower($role)) {
            abort(403, 'Access denied. ' . ucfirst($role) . ' role required.');
        }

        return $next($request);
    }
}