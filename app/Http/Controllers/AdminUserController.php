<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Restaurant_Data;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AdminUserController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['restaurantData', 'subscription'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phonenumber,
                    'status' => $user->status,
                    'email_verified' => $user->email_verified_at !== null,
                    'restaurant_name' => $user->restaurantData->restaurant_name ?? 'N/A',
                    'subscription_status' => $user->subscription->subscription_status ?? 'none',
                    'created_at' => $user->created_at,
                    'last_login' => $user->updated_at, // Approximation
                ];
            });

        $stats = [
            'total' => $users->count(),
            'approved' => $users->where('status', 'Approved')->count(),
            'pending' => $users->where('status', 'Pending')->count(),
            'rejected' => $users->where('status', 'Rejected')->count(),
        ];

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'stats' => $stats
        ]);
    }

    public function show($id): Response
    {
        $user = User::with(['restaurantData', 'subscription'])->findOrFail($id);
        
        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'middle_name' => $user->middle_name,
            'email' => $user->email,
            'phone' => $user->phonenumber,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'status' => $user->status,
            'role_id' => $user->role_id,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'restaurant' => $user->restaurantData ? [
                'name' => $user->restaurantData->restaurant_name,
                'address' => $user->restaurantData->restaurant_address,
                'contact_email' => $user->restaurantData->contact_email,
                'contact_phone' => $user->restaurantData->contact_phone,
                'cuisine_type' => $user->restaurantData->cuisine_type,
                'operating_hours' => $user->restaurantData->operating_hours,
            ] : null,
            'subscription' => $user->subscription ? [
                'status' => $user->subscription->subscription_status,
                'start_date' => $user->subscription->subscription_startDate,
                'end_date' => $user->subscription->subscription_endDate,
                'remaining_days' => $user->subscription->remaining_days,
                'is_trial' => $user->subscription->is_trial,
            ] : null,
        ];

        return Inertia::render('Admin/UserDetail', [
            'user' => $userData
        ]);
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected'
        ]);

        $user = User::findOrFail($id);
        $user->update(['status' => $request->status]);

        return back()->with('success', 'User status updated successfully.');
    }

    public function toggleEmailVerification($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'email_verified_at' => $user->email_verified_at ? null : now()
        ]);

        $action = $user->email_verified_at ? 'verified' : 'unverified';
        return back()->with('success', "User email {$action} successfully.");
    }

    public function resetPassword($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        
        // Generate a temporary password
        $tempPassword = 'temp' . rand(1000, 9999);
        $user->update(['password' => bcrypt($tempPassword)]);
        
        // In a real implementation, you would send this via email
        return back()->with('success', "Password reset. Temporary password: {$tempPassword}");
    }

    public function destroy($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        
        // Delete related data
        if ($user->restaurantData) {
            $user->restaurantData->delete();
        }
        
        if ($user->subscription) {
            $user->subscription->delete();
        }
        
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}