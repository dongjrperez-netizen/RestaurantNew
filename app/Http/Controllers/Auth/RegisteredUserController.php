<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Restaurant_Data;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
public function store(Request $request): RedirectResponse
{
    try {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'phonenumber' => 'required|string|max:20|unique:users,phonenumber',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            //restaurant data
            'restaurant_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20', // Changed to string to match procedure
            'contact_number' => 'required|string|max:20|unique:restaurant_data,contact_number',
        ]);

        // Call stored procedure with all 14 parameters
        DB::statement('CALL RegisterUserWithRestaurant(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $validated['last_name'],
            $validated['first_name'],
            $validated['middle_name'],
            $validated['date_of_birth'],
            $validated['gender'],
            $validated['email'],
            $validated['phonenumber'],
            Hash::make($validated['password']),
            //Restaurant Data (6 parameters)
            $validated['restaurant_name'],
            $validated['address'],
            $validated['postal_code'],
            $validated['contact_number']
        ]);

        $user = User::where('email', $validated['email'])->first();
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('register.documents');
    } catch (\Exception $e) {
        dd($e->getMessage()); 
    }
}

    public function showDocumentUpload(): Response
    {
        return Inertia::render('auth/Document');
    }
    
    public function store_doc(Request $request): RedirectResponse
    {
        try {
            // Validate that documents array exists and has at least one file
            $validated = $request->validate([
                'documents' => 'required|array|min:1',
                'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
                'document_types' => 'required|array|min:1',
                'document_types.*' => 'string|max:255',
            ]);

            $restaurantId = DB::table('restaurant_data')
                ->where('user_id', auth()->id())
                ->value('id');

            if (!$restaurantId) {
                return redirect()->back()->withErrors([
                    'error' => 'No restaurant linked to your account.'
                ]);
            }

            // Process each uploaded document
            $uploadedCount = 0;
            $documents = $request->file('documents');
            $documentTypes = $request->input('document_types', []);

            foreach ($documents as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('documents', 'public');
                    
                    // Get the document type, fallback to original extension if not provided
                    $docType = isset($documentTypes[$index]) 
                        ? $documentTypes[$index] 
                        : $file->getClientOriginalExtension();

                    DB::table('restaurant_documents')->insert([
                        'restaurant_id' => $restaurantId,
                        'doc_type'      => $docType,
                        'doc_file'      => $file->getClientOriginalName(),
                        'doc_path'      => $path,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    $uploadedCount++;
                }
            }

            if ($uploadedCount === 0) {
                return redirect()->back()->withErrors([
                    'error' => 'No valid documents were uploaded.'
                ]);
            }

            return redirect()->route('login')->with('success', $uploadedCount . ' document(s) uploaded successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Upload failed: ' . $e->getMessage()
            ]);
        }
    }
   
}



