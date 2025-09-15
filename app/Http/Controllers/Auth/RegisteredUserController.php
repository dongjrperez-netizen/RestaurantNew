<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Restaurant_Data;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
                // Personal Information Validation
                'first_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:50',
                    'regex:/^[a-zA-Z\s\-\'\.]+$/',
                ],
                'middle_name' => [
                    'required',
                    'string',
                    'min:1',
                    'max:50',
                    'regex:/^[a-zA-Z\s\-\'\.]+$/',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:50',
                    'regex:/^[a-zA-Z\s\-\'\.]+$/',
                ],
                'date_of_birth' => [
                    'required',
                    'date',
                    'before:today',
                    'after:1900-01-01',
                ],
                'gender' => [
                    'required',
                    'string',
                    'in:Male,Female,Other',
                ],

                // Contact Information Validation
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                ],

                // Password Validation
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'confirmed',
                ],
                'password_confirmation' => [
                    'required',
                    'string',
                    'same:password',
                ],

                // Restaurant Information Validation
                'restaurant_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:100',
                    'unique:restaurant_data,restaurant_name',
                ],
                'address' => [
                    'required',
                    'string',
                    'min:10',
                    'max:500',
                ],
                'postal_code' => [
                    'nullable',
                    'string',
                    'min:4',
                    'max:10',
                    'regex:/^[0-9]+$/',
                ],
                'contact_number' => [
                    'required',
                    'string',
                    'min:10',
                    'max:15',
                    'regex:/^[\+]?[0-9\-\(\)\s]+$/',
                    'unique:restaurant_data,contact_number',
                ],
            ], [
                // Custom Error Messages
                'first_name.required' => 'First name is required.',
                'first_name.min' => 'First name must be at least 2 characters.',
                'first_name.regex' => 'First name can only contain letters, spaces, hyphens, apostrophes, and dots.',

                'middle_name.required' => 'Middle name is required.',
                'middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens, apostrophes, and dots.',

                'last_name.required' => 'Last name is required.',
                'last_name.min' => 'Last name must be at least 2 characters.',
                'last_name.regex' => 'Last name can only contain letters, spaces, hyphens, apostrophes, and dots.',

                'date_of_birth.required' => 'Date of birth is required.',
                'date_of_birth.before' => 'Date of birth must be before today.',
                'date_of_birth.after' => 'Please enter a valid date of birth.',

                'gender.required' => 'Gender is required.',
                'gender.in' => 'Gender must be Male, Female, or Other.',

                'email.required' => 'Email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email address is already registered.',


                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',

                'password_confirmation.required' => 'Password confirmation is required.',
                'password_confirmation.same' => 'Password confirmation does not match the password.',

                'restaurant_name.required' => 'Restaurant name is required.',
                'restaurant_name.min' => 'Restaurant name must be at least 2 characters.',
                'restaurant_name.unique' => 'A restaurant with this name is already registered.',

                'address.required' => 'Restaurant address is required.',
                'address.min' => 'Address must be at least 10 characters.',

                'postal_code.min' => 'Postal code must be at least 4 digits.',
                'postal_code.max' => 'Postal code cannot be more than 10 digits.',
                'postal_code.regex' => 'Postal code must contain only numbers.',

                'contact_number.required' => 'Restaurant contact number is required.',
                'contact_number.min' => 'Contact number must be at least 10 digits.',
                'contact_number.regex' => 'Please enter a valid contact number format.',
                'contact_number.unique' => 'This contact number is already registered for another restaurant.',
            ]);

            DB::transaction(function () use ($validated) {

                $user = User::create([
                    'last_name' => $validated['last_name'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'gender' => $validated['gender'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                Restaurant_Data::create([
                    'user_id' => $user->id,
                    'restaurant_name' => $validated['restaurant_name'],
                    'address' => $validated['address'],
                    'postal_code' => $validated['postal_code'],
                    'contact_number' => $validated['contact_number'],
                ]);
            });

            $user = User::where('email', $validated['email'])->first();
            event(new Registered($user));
            Auth::login($user);

            return redirect()->route('register.documents');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show proper error messages
            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('User registration failed: '.$e->getMessage(), [
                'email' => $request->input('email'),
                'restaurant_name' => $request->input('restaurant_name'),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return user-friendly error message
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['error' => 'Registration failed. Please try again or contact support if the problem persists.']);
        }
    }

    public function showDocumentUpload(): Response
    {
        return Inertia::render('auth/Document');
    }

    public function store_doc(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'documents' => [
                    'required',
                    'array',
                    'min:1',
                    'max:10',
                ],
                'documents.*' => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,pdf,doc,docx',
                    'max:5120', // 5MB max per file
                    'min:1', // Minimum 1KB
                ],
                'document_types' => [
                    'required',
                    'array',
                    'min:1',
                    'max:10',
                ],
                'document_types.*' => [
                    'required',
                    'string',
                    'min:2',
                    'max:100',
                ],
            ], [
                // Document Validation Messages
                'documents.required' => 'At least one document is required.',
                'documents.min' => 'You must upload at least one document.',
                'documents.max' => 'You cannot upload more than 10 documents.',
                'documents.*.required' => 'Document file is required.',
                'documents.*.file' => 'Each upload must be a valid file.',
                'documents.*.mimes' => 'Documents must be in JPG, JPEG, PNG, PDF, DOC, or DOCX format.',
                'documents.*.max' => 'Each document must be smaller than 5MB.',
                'documents.*.min' => 'Document file appears to be empty.',

                'document_types.required' => 'Document types are required.',
                'document_types.min' => 'At least one document type is required.',
                'document_types.max' => 'You cannot specify more than 10 document types.',
                'document_types.*.required' => 'Document type is required.',
                'document_types.*.min' => 'Document type must be at least 2 characters.',
            ]);

            $restaurantId = DB::table('restaurant_data')
                ->where('user_id', auth()->id())
                ->value('id');

            if (! $restaurantId) {
                return redirect()->back()->withErrors([
                    'error' => 'No restaurant linked to your account.',
                ]);
            }

            $uploadedCount = 0;
            $documents = $request->file('documents');
            $documentTypes = $request->input('document_types', []);

            foreach ($documents as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('documents', 'public');

                    $docType = isset($documentTypes[$index])
                        ? $documentTypes[$index]
                        : $file->getClientOriginalExtension();

                    DB::table('restaurant_documents')->insert([
                        'restaurant_id' => $restaurantId,
                        'doc_type' => $docType,
                        'doc_file' => $file->getClientOriginalName(),
                        'doc_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $uploadedCount++;
                }
            }

            if ($uploadedCount === 0) {
                return redirect()->back()->withErrors([
                    'error' => 'No valid documents were uploaded.',
                ]);
            }

            return redirect()->route('login')->with('success', $uploadedCount.' document(s) uploaded successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Upload failed: '.$e->getMessage(),
            ]);
        }
    }
}
