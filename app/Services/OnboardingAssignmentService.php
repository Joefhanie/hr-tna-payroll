<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OnboardingAssignment;
use App\Models\OnboardingTask;
use App\Models\OnboardingTaskTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnboardingAssignmentService
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function ensureEmployeeIsOnboarded(Employee $employee, ?int $assignedBy = null): ?OnboardingAssignment
    {
        if (! $this->hasOnboardingTables() || ! $employee->hire_date || in_array((int) $employee->status, [4, 5], true)) {
            return null;
        }

        return DB::transaction(function () use ($employee, $assignedBy) {
            $assignment = OnboardingAssignment::firstOrCreate(
                ['employee_id' => $employee->id],
                [
                    'assigned_by' => $assignedBy,
                    'status' => OnboardingAssignment::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                ]
            );

            if ($assignment->tasks()->exists()) {
                return $assignment;
            }

            $this->ensureDefaultTaskTemplatesExist();

            $templates = OnboardingTaskTemplate::query()
                ->where('is_active', true)
                ->orderBy('sequence')
                ->get();

            foreach ($templates as $template) {
                $assignment->tasks()->create([
                    'onboarding_task_template_id' => $template->id,
                    'title' => $template->title,
                    'category' => $template->category,
                    'instructions' => $template->instructions,
                    'assigned_role' => $template->assigned_role,
                    'action_type' => $template->action_type,
                    'document_type' => $template->document_type,
                    'sequence' => $template->sequence,
                ]);
            }

            if ($assignment->employee?->user && $assignedBy && ($actor = User::find($assignedBy))) {
                $this->notificationService->notifyOnboardingStarted($assignment, $actor);
            }

            return $assignment->fresh('tasks');
        });
    }

    public function hasOnboardingTables(): bool
    {
        return Schema::hasTable('onboarding_assignments')
            && Schema::hasTable('onboarding_tasks')
            && Schema::hasTable('onboarding_task_templates');
    }

    private function ensureDefaultTaskTemplatesExist(): void
    {
        if (OnboardingTaskTemplate::query()->exists()) {
            return;
        }

        foreach ($this->defaultTaskTemplates() as $template) {
            OnboardingTaskTemplate::query()->create($template);
        }
    }

    private function defaultTaskTemplates(): array
    {
        return [
            [
                'title' => 'Sign employment contract',
                'category' => 'Documents',
                'instructions' => 'Download the latest employment contract, sign it, and upload the signed copy here.',
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                'document_type' => OnboardingTask::DOCUMENT_TYPE_EMPLOYMENT_CONTRACT,
                'sequence' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Upload required government IDs',
                'category' => 'Documents',
                'instructions' => 'Upload the government-issued IDs needed to complete your employee records.',
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                'document_type' => 'Government IDs',
                'sequence' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Upload profile picture',
                'category' => 'Documents',
                'instructions' => 'Upload a clear headshot for your employee profile and company directory.',
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_EMPLOYEE,
                'action_type' => OnboardingTask::ACTION_DOCUMENT_UPLOAD,
                'document_type' => 'Picture',
                'sequence' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Prepare workstation and accounts',
                'category' => 'IT Setup',
                'instructions' => 'Set up the employee workstation, email, and required system access.',
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_HR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
                'document_type' => null,
                'sequence' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Orientation with HR',
                'category' => 'HR',
                'instructions' => 'Conduct the HR orientation and explain company policies.',
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_HR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
                'document_type' => null,
                'sequence' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Team introduction',
                'category' => 'Training',
                'instructions' => 'Introduce the employee to the team and immediate support contacts.',
                'assigned_role' => OnboardingTask::ASSIGNED_ROLE_SUPERVISOR,
                'action_type' => OnboardingTask::ACTION_CHECKLIST,
                'document_type' => null,
                'sequence' => 6,
                'is_active' => true,
            ],
        ];
    }
}
