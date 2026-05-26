<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;

class LeaveRequestService
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public static function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function submit(User $user, array $validated): Leave
    {
        if ((int) $user->role === 1 && (int) $validated['employee_id'] !== (int) $user->employee_id) {
            abort(403);
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $leave = Leave::create([
            'employee_id' => (int) $validated['employee_id'],
            'leave_type_id' => (int) $validated['leave_type_id'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_requested' => (float) ($startDate->diffInDays($endDate) + 1),
            'reason' => $validated['reason'] ?? null,
            'status' => 1,
        ]);

        $this->notificationService->notifyLeaveRequested($leave, $user);

        return $leave;
    }
}
