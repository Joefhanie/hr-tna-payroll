<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('field_records', function (Blueprint $table) {
            $table->id();
            $table->string('empid', 30);
            $table->date('Date')->nullable();
            $table->string('sup_id', 30);
            $table->integer('company_id');
            $table->dateTime('time');
            $table->integer('function');
            $table->integer('status');
            $table->text('remarks')->nullable();
            $table->string('location', 120)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->integer('created_by');
            $table->dateTime('updated_at')->nullable();
            $table->integer('updated_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->integer('deleted_by')->nullable();

                $table->index('empid', 'idx_field_records_empid');
                $table->index('sup_id', 'idx_field_records_sup_id');
            });

        // Insert initial data
        DB::table('field_records')->insert([
            ['id' => 1, 'empid' => 'JP001', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:00', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:00', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 2, 'empid' => 'JP001', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:01', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:01', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 3, 'empid' => 'JP001', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:04', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:04', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 4, 'empid' => 'JP001', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:05', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:05', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 5, 'empid' => 'JP001', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:08', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:08', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 6, 'empid' => 'JP001', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:11', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:11', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 7, 'empid' => 'CG010', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:36', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:36', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
            ['id' => 8, 'empid' => 'PM006', 'Date' => '2026-05-20', 'sup_id' => 'AD002', 'company_id' => 1, 'time' => '2026-05-19 16:41:44', 'function' => 1, 'status' => 1, 'remarks' => 'Developing Websites', 'created_at' => '2026-05-19 16:41:44', 'notes' => null, 'created_by' => 2, 'updated_at' => null, 'updated_by' => null, 'deleted_at' => null, 'deleted_by' => null],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_records');
    }
};
