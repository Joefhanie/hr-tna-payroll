<?php

use App\Models\OnboardingTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_task_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_task_templates', 'instructions')) {
                $table->text('instructions')->nullable()->after('category');
            }

            if (!Schema::hasColumn('onboarding_task_templates', 'action_type')) {
                $table->string('action_type', 40)->default(OnboardingTask::ACTION_CHECKLIST)->after('assigned_role');
            }

            if (!Schema::hasColumn('onboarding_task_templates', 'document_type')) {
                $table->string('document_type', 100)->nullable()->after('action_type');
            }
        });

        Schema::table('onboarding_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_tasks', 'instructions')) {
                $table->text('instructions')->nullable()->after('category');
            }

            if (!Schema::hasColumn('onboarding_tasks', 'action_type')) {
                $table->string('action_type', 40)->default(OnboardingTask::ACTION_CHECKLIST)->after('assigned_role');
            }

            if (!Schema::hasColumn('onboarding_tasks', 'document_type')) {
                $table->string('document_type', 100)->nullable()->after('action_type');
            }

            if (!Schema::hasColumn('onboarding_tasks', 'submission_notes')) {
                $table->text('submission_notes')->nullable()->after('document_type');
            }

            if (!Schema::hasColumn('onboarding_tasks', 'submission_file_name')) {
                $table->string('submission_file_name')->nullable()->after('submission_notes');
            }

            if (!Schema::hasColumn('onboarding_tasks', 'submission_file_path')) {
                $table->string('submission_file_path')->nullable()->after('submission_file_name');
            }

            if (!Schema::hasColumn('onboarding_tasks', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submission_file_path');
            }
        });

        DB::table('onboarding_task_templates')
            ->where('title', 'Sign employment contract')
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_ACKNOWLEDGEMENT,
                'instructions' => 'Open the onboarding page and confirm that you have reviewed and accepted the employment contract.',
                'document_type' => null,
            ]);

        DB::table('onboarding_task_templates')
            ->where('title', 'Submit government IDs')
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                'instructions' => 'Upload a clear copy of your required government identification documents.',
                'document_type' => 'Government IDs',
            ]);

        DB::table('onboarding_task_templates')
            ->where('title', 'Orientation with HR')
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_HR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
                'instructions' => 'HR should schedule and complete the new hire orientation session.',
            ]);

        DB::table('onboarding_task_templates')
            ->whereIn('title', ['Laptop & accessories setup', 'Email & system access', 'Team introduction'])
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_SUPERVISOR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
            ]);

        DB::table('onboarding_tasks')
            ->where('title', 'Sign employment contract')
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_ACKNOWLEDGEMENT,
                'instructions' => 'Open the onboarding page and confirm that you have reviewed and accepted the employment contract.',
                'document_type' => null,
            ]);

        DB::table('onboarding_tasks')
            ->where('title', 'Submit government IDs')
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                'instructions' => 'Upload a clear copy of your required government identification documents.',
                'document_type' => 'Government IDs',
            ]);

        DB::table('onboarding_tasks')
            ->where('title', 'Orientation with HR')
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_HR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
                'instructions' => 'HR should schedule and complete the new hire orientation session.',
            ]);

        DB::table('onboarding_tasks')
            ->whereIn('title', ['Laptop & accessories setup', 'Email & system access', 'Team introduction'])
            ->update([
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_SUPERVISOR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
            ]);
    }

    public function down(): void
    {
        Schema::table('onboarding_task_templates', function (Blueprint $table) {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('onboarding_task_templates', 'instructions') ? 'instructions' : null,
                Schema::hasColumn('onboarding_task_templates', 'action_type') ? 'action_type' : null,
                Schema::hasColumn('onboarding_task_templates', 'document_type') ? 'document_type' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('onboarding_tasks', function (Blueprint $table) {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('onboarding_tasks', 'instructions') ? 'instructions' : null,
                Schema::hasColumn('onboarding_tasks', 'action_type') ? 'action_type' : null,
                Schema::hasColumn('onboarding_tasks', 'document_type') ? 'document_type' : null,
                Schema::hasColumn('onboarding_tasks', 'submission_notes') ? 'submission_notes' : null,
                Schema::hasColumn('onboarding_tasks', 'submission_file_name') ? 'submission_file_name' : null,
                Schema::hasColumn('onboarding_tasks', 'submission_file_path') ? 'submission_file_path' : null,
                Schema::hasColumn('onboarding_tasks', 'submitted_at') ? 'submitted_at' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
