<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();

            // Personal Details
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // Contact Information
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('phone_alt')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            // Employment Information
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('manager_name')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->enum('employment_status', ['active', 'inactive', 'terminated', 'on_leave'])->default('active');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'temporary'])->default('full_time');

            // Government IDs and Compliance
            $table->string('ssn')->nullable()->unique();
            $table->string('tax_id')->nullable()->unique();
            $table->string('driver_license')->nullable()->unique();
            $table->string('passport_number')->nullable()->nullable();

            // Salary Information
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->enum('pay_frequency', ['weekly', 'bi_weekly', 'monthly', 'annual'])->default('monthly');
            $table->decimal('hourly_rate', 8, 2)->nullable();

            // Bank Information
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();

            // Additional Information
            $table->text('notes')->nullable();
            $table->string('profile_photo')->nullable();

            $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

