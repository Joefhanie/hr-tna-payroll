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
        $credentialsResponse = $this->post('/register', [
            'username' => 'jane.doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 4,
        ]);

        $credentialsResponse->assertRedirect('/register/profile');

        $profileResponse = $this->post('/register/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '09123456789',
            'city' => 'Pasig',
            'country' => 'Philippines',
        ]);

        $profileResponse->assertRedirect('/register/employment');

        $employmentResponse = $this->post('/register/employment', [
            'employment_type' => 1,
            'status' => 1,
            'hire_date' => now()->toDateString(),
        ]);

        $employmentResponse->assertRedirect('/dashboard');

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotSame('secret123', $user->password);
        $this->assertSame('jane.doe', $user->username);
        $this->assertAuthenticatedAs($user);
    }
}
