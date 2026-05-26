<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SalaryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryRecordInactiveEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_salary_record_can_be_fully_edited(): void
    {
        $user = User::create([
            'name' => 'HR Admin',
            'username' => 'hr.admin',
            'email' => 'hr@example.com',
            'password' => bcrypt('password'),
            'role' => 4,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => 2,
            'employment_type' => 1,
            'hire_date' => '2026-01-01',
        ]);

        $salaryRecord = SalaryRecord::create([
            'employee_id' => $employee->id,
            'amount' => 50000.00,
            'pay_frequency' => 5,
            'daily_divisor' => 21.80,
            'effective_date' => '2026-01-01',
            'end_date' => null,
            'reason' => 'Initial',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->put("/salary/{$salaryRecord->id}", [
                'amount' => 60000.00,
                'pay_frequency' => 5,
                'daily_divisor' => 22.00,
                'effective_date' => '2026-02-01',
                'end_date' => null,
                'reason' => 'Promotion',
                'notes' => 'New notes',
            ]);

        $response->assertRedirect("/employees/{$employee->id}/salary");

        $salaryRecord->refresh();
        $this->assertEquals(60000.00, (float) $salaryRecord->amount);
        $this->assertEquals(22.00, (float) $salaryRecord->daily_divisor);
        $this->assertEquals('2026-02-01', $salaryRecord->effective_date->toDateString());
        $this->assertEquals('Promotion', $salaryRecord->reason);
        $this->assertEquals('New notes', $salaryRecord->notes);
    }

    public function test_inactive_salary_record_cannot_have_non_reason_fields_edited(): void
    {
        $user = User::create([
            'name' => 'HR Admin',
            'username' => 'hr.admin',
            'email' => 'hr@example.com',
            'password' => bcrypt('password'),
            'role' => 4,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => 2,
            'employment_type' => 1,
            'hire_date' => '2026-01-01',
        ]);

        $salaryRecord = SalaryRecord::create([
            'employee_id' => $employee->id,
            'amount' => 50000.00,
            'pay_frequency' => 5,
            'daily_divisor' => 21.80,
            'effective_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'reason' => 'Initial',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->put("/salary/{$salaryRecord->id}", [
                'amount' => 60000.00,
                'pay_frequency' => 5,
                'daily_divisor' => 22.00,
                'effective_date' => '2026-02-01',
                'end_date' => '2026-02-28',
                'reason' => 'Updated Reason Only',
                'notes' => 'New notes',
            ]);

        $response->assertRedirect("/employees/{$employee->id}/salary");

        $salaryRecord->refresh();
        $this->assertEquals('Updated Reason Only', $salaryRecord->reason);
        $this->assertEquals('New notes', $salaryRecord->notes);

        $this->assertEquals(50000.00, (float) $salaryRecord->amount);
        $this->assertEquals(21.80, (float) $salaryRecord->daily_divisor);
        $this->assertEquals('2026-01-01', $salaryRecord->effective_date->toDateString());
        $this->assertEquals('2026-01-31', $salaryRecord->end_date->toDateString());
    }
}
