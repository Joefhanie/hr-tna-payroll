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
        // remove foreign keys referencing users, if present
        try {
            if (Schema::hasTable('sessions')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            }
        } catch (\Throwable $e) {
            // ignore if FK doesn't exist
        }

        try {
            if (Schema::hasTable('payrolls')) {
                Schema::table('payrolls', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // finally drop users table
        if (Schema::hasTable('users')) {
            Schema::drop('users');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // recreate minimal users table
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->tinyInteger('role')->unsigned()->default(4)->comment('1=Employee,2=Supervisor,3=OIC,4=HR');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // re-add foreign keys if tables exist
        try {
            if (Schema::hasTable('sessions')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('payrolls')) {
                Schema::table('payrolls', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
