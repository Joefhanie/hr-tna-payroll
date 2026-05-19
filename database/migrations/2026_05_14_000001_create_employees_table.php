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
                $table->string('employee_code', 30)->unique();
                $table->string('first_name', 80);
                $table->string('last_name', 80);
                $table->string('middle_name', 80)->nullable();
                $table->string('email', 160)->unique();
                $table->string('phone', 30)->nullable();
                $table->date('birth_date')->nullable();
                $table->enum('gender', ['Male', 'Female', 'Non-binary', 'Prefer not to say'])->nullable();
                $table->string('nationality', 80)->nullable();
                $table->enum('marital_status', ['Single', 'Married', 'Widowed', 'Divorced', 'Separated'])->nullable();
                $table->string('address_line1', 200)->nullable();
                $table->string('address_line2', 200)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 80)->default('Philippines')->nullable();
                $table->integer('status')->default(2);
                $table->integer('employment_type')->default(1);
                $table->date('hire_date');
                $table->date('regularization_date')->nullable();
                $table->date('termination_date')->nullable();
                $table->text('termination_reason')->nullable();
                $table->unsignedBigInteger('position_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->timestamps();
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
