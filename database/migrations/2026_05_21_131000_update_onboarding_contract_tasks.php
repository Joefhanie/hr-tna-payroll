<?php

use App\Models\OnboardingTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('onboarding_task_templates')) {
            DB::table('onboarding_task_templates')
                ->whereIn('title', ['Review and sign employment contract', 'Sign employment contract'])
                ->update([
                    'title' => 'Sign employment contract',
                    'instructions' => 'Download the latest employment contract, sign it, and upload the signed copy here.',
                    'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                    'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                    'document_type' => OnboardingTask::DOCUMENT_TYPE_EMPLOYMENT_CONTRACT,
                ]);
        }

        if (Schema::hasTable('onboarding_tasks')) {
            DB::table('onboarding_tasks')
                ->whereIn('title', ['Review and sign employment contract', 'Sign employment contract'])
                ->update([
                    'title' => 'Sign employment contract',
                    'instructions' => 'Download the latest employment contract, sign it, and upload the signed copy here.',
                    'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                    'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                    'document_type' => OnboardingTask::DOCUMENT_TYPE_EMPLOYMENT_CONTRACT,
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('onboarding_task_templates')) {
            DB::table('onboarding_task_templates')
                ->where('title', 'Sign employment contract')
                ->update([
                    'instructions' => 'Open the onboarding page and confirm that you have reviewed and accepted the employment contract.',
                    'action_type' => OnboardingTask::ACTION_ACKNOWLEDGEMENT,
                    'document_type' => null,
                ]);
        }

        if (Schema::hasTable('onboarding_tasks')) {
            DB::table('onboarding_tasks')
                ->where('title', 'Sign employment contract')
                ->update([
                    'instructions' => 'Open the onboarding page and confirm that you have reviewed and accepted the employment contract.',
                    'action_type' => OnboardingTask::ACTION_ACKNOWLEDGEMENT,
                    'document_type' => null,
                ]);
        }
    }
};
