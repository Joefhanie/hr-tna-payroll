<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\OnboardingAssignment;
use App\Models\OnboardingTask;
use App\Models\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationService
{
    public function notifyLeaveRequested(Leave $leave, User $requester): void
    {
        $leave->loadMissing('employee.manager.user');

        $recipients = $this->hrUsers();
        $managerUser = $leave->employee?->manager?->user;

        if ($managerUser && $managerUser->id !== $requester->id) {
            $recipients->push($managerUser);
        }

        $this->send($recipients, new SystemNotification(
            'leave-request',
            'Leave request submitted',
            trim((string) ($leave->employee?->full_name ?? 'An employee')) . ' submitted a leave request for ' . optional($leave->start_date)->format('M d, Y') . ' to ' . optional($leave->end_date)->format('M d, Y') . '.',
            route('leave.index'),
            'ti ti-calendar-event',
            [
                'leave_id' => $leave->id,
                'employee_id' => $leave->employee_id,
            ]
        ));
    }

    public function notifyProfileUpdateRequested(ProfileUpdateRequest $request, User $requester): void
    {
        $request->loadMissing('employee.manager.user');

        $recipients = $this->hrUsers();
        $managerUser = $request->employee?->manager?->user;

        if ($managerUser && $managerUser->id !== $requester->id) {
            $recipients->push($managerUser);
        }

        $this->send($recipients, new SystemNotification(
            'profile-update-request',
            'Profile update request submitted',
            trim((string) ($request->employee?->full_name ?? 'An employee')) . ' requested changes to their profile information.',
            route('self-service.profile', $request->employee_id),
            'ti ti-user-edit',
            [
                'profile_update_request_id' => $request->id,
                'employee_id' => $request->employee_id,
            ]
        ));
    }

    public function notifyOnboardingStarted(OnboardingAssignment $assignment, User $actor): void
    {
        $assignment->loadMissing('employee.user');

        $employeeUser = $assignment->employee?->user;
        if (!$employeeUser || $employeeUser->id === $actor->id) {
            return;
        }

        $this->send(collect([$employeeUser]), new SystemNotification(
            'onboarding-started',
            'Onboarding assigned',
            'Your onboarding tasks are ready. Please review the onboarding checklist and complete the assigned items.',
            route('onboarding', ['employee' => $assignment->employee_id]),
            'ti ti-rocket',
            [
                'onboarding_assignment_id' => $assignment->id,
                'employee_id' => $assignment->employee_id,
            ]
        ));
    }

    public function notifyOnboardingTaskCompleted(OnboardingTask $task, User $actor): void
    {
        $task->loadMissing([
            'assignment.employee.user',
            'assignment.employee.manager.user',
            'assignment.assignedBy',
            'completedBy',
        ]);

        $recipients = $this->hrUsers();

        if ($task->assignment?->assignedBy && $task->assignment->assignedBy->id !== $actor->id) {
            $recipients->push($task->assignment->assignedBy);
        }

        $employeeUser = $task->assignment?->employee?->user;
        if ($employeeUser && $employeeUser->id !== $actor->id) {
            $recipients->push($employeeUser);
        }

        $managerUser = $task->assignment?->employee?->manager?->user;
        if ($managerUser && $managerUser->id !== $actor->id) {
            $recipients->push($managerUser);
        }

        $ownerLabel = match ($task->assigned_role) {
            OnboardingTask::ASSIGNED_ROLE_EMPLOYEE => 'employee',
            OnboardingTask::ASSIGNED_ROLE_SUPERVISOR => 'supervisor',
            OnboardingTask::ASSIGNED_ROLE_HR => 'HR',
            default => 'team member',
        };

        $this->send($recipients, new SystemNotification(
            'onboarding-task-completed',
            'Onboarding task completed',
            trim((string) ($task->completedBy?->name ?? 'A team member')) . ' completed the ' . $ownerLabel . ' onboarding task "' . $task->title . '".',
            route('onboarding', ['employee' => $task->assignment?->employee_id]),
            'ti ti-circle-check',
            [
                'onboarding_task_id' => $task->id,
                'onboarding_assignment_id' => $task->onboarding_assignment_id,
                'employee_id' => $task->assignment?->employee_id,
            ]
        ));
    }

    public function notifyLeaveDecision(Leave $leave, User $reviewer, string $decision, ?string $note = null): void
    {
        $leave->loadMissing('employee.user');

        $employeeUser = $leave->employee?->user;
        if (! $employeeUser || $employeeUser->id === $reviewer->id) {
            return;
        }

        $decisionLabel = $decision === 'approved' ? 'approved' : 'rejected';
        $icon = $decision === 'approved' ? 'ti ti-circle-check' : 'ti ti-circle-x';

        $message = trim((string) ($reviewer->name ?? 'HR')) . ' ' . $decisionLabel . ' your leave request for ' . optional($leave->start_date)->format('M d, Y') . ' to ' . optional($leave->end_date)->format('M d, Y') . '.';

        if ($note) {
            $message .= ' Note: ' . $note;
        }

        $this->send(collect([$employeeUser]), new SystemNotification(
            'leave-' . $decisionLabel,
            'Leave request ' . $decisionLabel,
            $message,
            route('self-service.profile', $leave->employee_id),
            $icon,
            [
                'leave_id' => $leave->id,
                'employee_id' => $leave->employee_id,
                'decision' => $decisionLabel,
                'reviewer_id' => $reviewer->id,
                'note' => $note,
            ]
        ));
    }

    public function notifyProfileUpdateDecision(ProfileUpdateRequest $request, User $reviewer, string $decision, ?string $note = null): void
    {
        $request->loadMissing('employee.user');

        $employeeUser = $request->employee?->user;
        if (! $employeeUser || $employeeUser->id === $reviewer->id) {
            return;
        }

        $decisionLabel = $decision === 'approved' ? 'approved' : 'rejected';
        $icon = $decision === 'approved' ? 'ti ti-circle-check' : 'ti ti-circle-x';

        $message = trim((string) ($reviewer->name ?? 'HR')) . ' ' . $decisionLabel . ' your profile update request.';

        if ($note) {
            $message .= ' Note: ' . $note;
        }

        $this->send(collect([$employeeUser]), new SystemNotification(
            'profile-update-' . $decisionLabel,
            'Profile update request ' . $decisionLabel,
            $message,
            route('self-service.profile', $request->employee_id),
            $icon,
            [
                'profile_update_request_id' => $request->id,
                'employee_id' => $request->employee_id,
                'decision' => $decisionLabel,
                'reviewer_id' => $reviewer->id,
                'note' => $note,
            ]
        ));
    }

    private function hrUsers(): Collection
    {
        return User::query()
            ->where('role', 4)
            ->get();
    }

    private function send(Collection $recipients, SystemNotification $notification): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $uniqueRecipients = $recipients
            ->filter(fn ($recipient) => $recipient instanceof User)
            ->unique('id')
            ->values();

        if ($uniqueRecipients->isEmpty()) {
            return;
        }

        foreach ($uniqueRecipients as $recipient) {
            DB::table('notifications')->insert([
                'recipient_id' => $recipient->id,
                'type' => $notification->type(),
                'notifiable_type' => User::class,
                'notifiable_id' => $recipient->id,
                'data' => json_encode([
                    'type' => $notification->type(),
                    'title' => $notification->title(),
                    'message' => $notification->message(),
                    'url' => $notification->url(),
                    'icon' => $notification->icon(),
                    'meta' => $notification->meta(),
                ]),
                'title' => $notification->title(),
                'message' => $notification->message(),
                'link' => $notification->url(),
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'read_at' => null,
            ]);
        }
    }
}
