<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the user profile form.
     */
    public function show()
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if ($employee) {
            $employee->load(['department', 'position', 'manager']);
        }

        return view('profile.show', [
            'user' => $user,
            'employee' => $employee,
        ]);
    }

    /**
     * Update the user profile details and profile picture.
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        // Validation rules
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ];

        if ($employee) {
            $rules = array_merge($rules, [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'phone' => ['nullable', 'string', 'max:30'],
                'birth_date' => ['nullable', 'date'],
                'gender' => ['nullable', 'string', 'max:20'],
                'marital_status' => ['nullable', 'string', 'max:30'],
                'address_line1' => ['nullable', 'string', 'max:255'],
                'address_line2' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'province' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'country' => ['nullable', 'string', 'max:100'],
            ]);
            
            $rules['email'][] = Rule::unique('employees', 'email')->ignore($employee->id);
        }

        $validated = $request->validate($rules);

        // Update User
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($employee) {
            // Handle Profile Picture upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                
                // Delete old profile picture if exists
                if ($employee->profile_picture) {
                    Storage::disk('public')->delete($employee->profile_picture);
                }

                // Store the new picture
                $storedPath = $file->store('profile_pictures', 'public');
                $employee->profile_picture = $storedPath;
            }

            // Update Employee Details
            $employee->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'address_line1' => $validated['address_line1'] ?? null,
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? null,
            ]);
        }

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profile updated successfully.');
    }
}
