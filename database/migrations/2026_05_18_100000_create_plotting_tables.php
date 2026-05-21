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
        if (!Schema::hasTable('employee_plottings')) {
            Schema::create('employee_plottings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('employee_id');
                $table->unsignedInteger('supervisor_id')->nullable();
                $table->date('date');
                $table->string('location', 120)->nullable();
                $table->decimal('amount', 14, 2)->default(0.00);
                $table->timestamps();

                $table->unique(['employee_id', 'date'], 'uq_emp_date');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('supervisor_id')->references('id')->on('employees')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_plottings');
    }
};
