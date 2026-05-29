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
        if (!Schema::hasTable('attendance')) {
            Schema::create('attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emp_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->enum('punch_type', ['in', 'out']);
                $table->date('attendance_date');
                $table->time('time')->nullable();
                $table->tinyInteger('status')->default(1)->comment('1=Present, 2=Late, 3=Absent, 4=Excused');
                $table->timestamps();

                $table->index(['emp_id', 'attendance_date'], 'idx_attendance_emp_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};

