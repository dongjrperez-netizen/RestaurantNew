<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountUpdateController extends Controller{
    /**
     * Fetch the authenticated user's data.
     */

    public function show()
    {
        $user = Auth::user();
        return Inertia::render('AccountUpdate');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return redirect()->back()->with('success', 'Account updated successfully!');
    }

    public function fetchUser(Request $request)
    {
        $user = $request->user();
        return response()->json($user);

  
    }
}
