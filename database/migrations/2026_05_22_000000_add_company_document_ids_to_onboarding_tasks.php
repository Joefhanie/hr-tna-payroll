<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_tasks', 'company_document_ids')) {
                $table->json('company_document_ids')->nullable()->after('document_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('onboarding_tasks', 'company_document_ids')) {
                $table->dropColumn('company_document_ids');
            }
        });
    }
};
