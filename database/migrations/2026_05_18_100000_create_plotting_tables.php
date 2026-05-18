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
        Schema::create('supervisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supervisor_id');
            $table->string('location', 120);
            $table->date('date');
            $table->timestamps();

            $table->unique(['supervisor_id', 'date'], 'uq_sv_date');
            $table->foreign('supervisor_id')->references('id')->on('employees')->onDelete('cascade');
        });

        Schema::create('employee_plottings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->date('date');
            $table->string('location', 120)->nullable();
            $table->decimal('amount', 14, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['employee_id', 'date'], 'uq_emp_date');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_plottings');
        Schema::dropIfExists('supervisor_assignments');
    }
};
