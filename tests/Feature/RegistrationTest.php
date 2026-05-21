<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_users_can_register_with_a_hashed_password(): void
    {
        $personalResponse = $this->post('/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'Female',
            'birth_date' => '1995-05-15',
            'nationality' => 'Filipino',
            'marital_status' => 'Single',
        ]);

        $personalResponse->assertRedirect('/register/profile');

        $contactResponse = $this->post('/register/profile', [
            'username' => 'jane.doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '09123456789',
            'city' => 'Pasig',
            'country' => 'Philippines',
        ]);

        $contactResponse->assertRedirect('/register/employment');

        $employmentResponse = $this->post('/register/employment', [
            'employment_type' => 1,
            'hire_date' => now()->toDateString(),
        ]);

        $employmentResponse->assertRedirect('/dashboard');

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password);
        $this->assertSame('jane.doe', $user->username);
        $this->assertNotNull($user->employee_id);
        
        $employee = \App\Models\Employee::find($user->employee_id);
        $this->assertNotNull($employee);
        $this->assertSame(2, $employee->status);
        
        $this->assertAuthenticatedAs($user);
    }
}
