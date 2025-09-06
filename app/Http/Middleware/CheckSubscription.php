<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Usersubscription;
use Carbon\Carbon;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if ($user) {
            $activeSubscription = Usersubscription::where('user_id', $user->id)
                ->where('subscription_status', 'active')
                ->first();
            
            if ($activeSubscription) {
                $now = Carbon::now();
                $endDate = Carbon::parse($activeSubscription->subscription_endDate);
                
                // Check if subscription has expired
                if ($now->greaterThan($endDate)) {
                    // Update subscription status to archive
                    $activeSubscription->update([
                        'subscription_status' => 'archive',
                        'remaining_days' => 0
                    ]);
                    
                    // Update user role back to 1 (regular user)
                    // $user->update(['role_id' => 1]);

                    return redirect()->route('subscriptions.renew')
                        ->withErrors(['error' => 'Your subscription has expired. Please renew your subscription to continue.']);
                }
            } else {
                // No active subscription found, redirect to subscriptions page
                return redirect()->route('subscriptions.index')
                    ->withErrors(['error' => 'You need an active subscription to access this feature.']);
            }
        }
        
        return $next($request);
    }
}
