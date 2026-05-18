<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', $this->registrationViewData('credentials', [
            'title' => 'Create a secure staff account.',
            'description' => 'Start with login credentials, then continue to the employee profile pages.',
            'formAction' => route('register.store'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username', 'alpha_dash'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:employees,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $request->session()->put('registration.account', [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'remember' => (bool) $request->input('remember'),
        ]);

        return redirect()->route('register.profile');
    }

    public function profile(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('registration.account')) {
            return redirect()->route('register');
        }

        return view('auth.register', $this->registrationViewData('profile', [
            'title' => 'Complete the employee profile.',
            'description' => 'Add the personal and contact details that would normally appear on the employee form.',
            'formAction' => route('register.profile.store'),
        ]));
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Non-binary,Prefer not to say'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'marital_status' => ['nullable', 'in:Single,Married,Widowed,Divorced,Separated'],
            'address_line1' => ['nullable', 'string', 'max:200'],
            'address_line2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
        ]);

        $request->session()->put('registration.profile', $validated);

        return redirect()->route('register.employment');
    }

    public function employment(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('registration.account')) {
            return redirect()->route('register');
        }

        if (! $request->session()->has('registration.profile')) {
            return redirect()->route('register.profile');
        }

        return view('auth.register', $this->registrationViewData('employment', [
            'title' => 'Finish the employment details.',
            'description' => 'Select the role, department, and onboarding details before creating the account.',
            'formAction' => route('register.employment.store'),
        ]));
    }

    public function storeEmployment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employment_type' => ['required', 'in:1,2,3,4'],
            'status' => ['required', 'in:1,2,3,4,5'],
            'hire_date' => ['required', 'date'],
            'regularization_date' => ['nullable', 'date'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
        ]);

        $account = $request->session()->get('registration.account');
        $profile = $request->session()->get('registration.profile');

        if (! is_array($account) || ! is_array($profile)) {
            return redirect()->route('register');
        }

        $user = DB::transaction(function () use ($account, $profile, $validated) {
            $user = User::create([
                'name' => $account['name'],
                'username' => $account['username'],
                'email' => $account['email'],
                'password' => $account['password'],
                'status' => 2,
            ]);

            $employee = Employee::create([
                'employee_code' => $this->generateTemporaryEmployeeCode(),
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'middle_name' => $profile['middle_name'] ?? null,
                'email' => $account['email'],
                'phone' => $profile['phone'] ?? null,
                'birth_date' => $profile['birth_date'] ?? null,
                'gender' => $profile['gender'] ?? null,
                'nationality' => $profile['nationality'] ?? null,
                'marital_status' => $profile['marital_status'] ?? null,
                'address_line1' => $profile['address_line1'] ?? null,
                'address_line2' => $profile['address_line2'] ?? null,
                'city' => $profile['city'] ?? null,
                'province' => $profile['province'] ?? null,
                'postal_code' => $profile['postal_code'] ?? null,
                'country' => $profile['country'] ?? null,
                'employment_type' => $validated['employment_type'],
                'status' => $validated['status'],
                'hire_date' => $validated['hire_date'],
                'regularization_date' => $validated['regularization_date'] ?? null,
                'position_id' => $validated['position_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'manager_id' => $validated['manager_id'] ?? null,
            ]);

            $employee->update([
                'employee_code' => $this->generateEmployeeCode(
                    $employee->first_name,
                    $employee->last_name,
                    $employee->id,
                ),
            ]);

            $user->update([
                'employee_id' => $employee->id,
            ]);

            return $user;
        });

        $request->session()->forget(['registration.account', 'registration.profile']);

        Auth::login($user, (bool) $account['remember']);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function registrationViewData(string $step, array $overrides = []): array
    {
        $departments = Department::all();
        $positions = Position::all();
        $managers = Employee::where('status', 1)
            ->where('employment_type', 1)
            ->get();

        return array_merge([
            'step' => $step,
            'departments' => $departments,
            'positions' => $positions,
            'managers' => $managers,
        ], $overrides);
    }

    private function generateEmployeeCode(string $firstName, string $lastName, int $id): string
    {
        $firstInitial = strtoupper(substr(trim($firstName), 0, 1));
        $lastInitial = strtoupper(substr(trim($lastName), 0, 1));

        return $firstInitial . $lastInitial . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    private function generateTemporaryEmployeeCode(): string
    {
        return 'TMP' . now()->format('YmdHis') . random_int(1000, 9999);
    }
}
