<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!app()->runningInConsole()) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('employees') && \Illuminate\Support\Facades\Schema::hasTable('leave_requests')) {
                    $today = now()->toDateString();

                    // 1. Revert employees marked as On Leave (3) whose leave duration has ended (no approved leave for today)
                    \App\Models\Employee::where('status', 3)
                        ->whereNotExists(function ($query) use ($today) {
                            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('leave_requests')
                                ->whereColumn('leave_requests.employee_id', 'employees.id')
                                ->where('leave_requests.status', 2) // Approved
                                ->where('leave_requests.start_date', '<=', $today)
                                ->where('leave_requests.end_date', '>=', $today);
                        })
                        ->update(['status' => 1]); // Revert to Active

                    // 2. Set employees to On Leave (3) if they have an approved leave covering today
                    \App\Models\Employee::whereIn('status', [1, 2])
                        ->whereExists(function ($query) use ($today) {
                            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('leave_requests')
                                ->whereColumn('leave_requests.employee_id', 'employees.id')
                                ->where('leave_requests.status', 2) // Approved
                                ->where('leave_requests.start_date', '<=', $today)
                                ->where('leave_requests.end_date', '>=', $today);
                        })
                        ->update(['status' => 3]);

                    // 3. Auto-mark employees as Absent (insert attendance status 3)
                    // If their shift start time on a scheduled day has passed, and they have no attendance record.
                    // We check today and the past 7 days to cover any gaps.
                    // 3. Auto-mark employees as Absent/Excused, and sync with latest shift schedules/leaves
                    if (\Illuminate\Support\Facades\Schema::hasTable('attendance')) {
                        $checkDaysCount = 7;
                        $allEmployees = \App\Models\Employee::whereHas('user')->get();
                        
                        for ($i = 0; $i < $checkDaysCount; $i++) {
                            $checkDate = now()->subDays($i);
                            $dateStr = $checkDate->toDateString();
                            $dayOfWeek = $checkDate->format('D');
                            
                            foreach ($allEmployees as $employee) {
                                /** @var \App\Models\Employee $employee */
                                $user = $employee->user;
                                if (!$user) continue;
                                
                                // Get active shift for this day
                                $dayShift = $employee->getActiveShiftForDate($checkDate);
                                
                                // Find any existing attendance record for this day
                                $existingRecord = \App\Models\Attendance::where('user_id', $user->id)
                                    ->where('attendance_date', $dateStr)
                                    ->first();
                                
                                $isScheduled = $dayShift && is_array($dayShift->days_of_week) && in_array($dayOfWeek, $dayShift->days_of_week);
                                
                                if ($isScheduled) {
                                    // Get shift start datetime in Asia/Manila timezone
                                    $shiftStart = \Carbon\Carbon::parse($dateStr . ' ' . $dayShift->start_time, 'Asia/Manila');
                                    
                                    // Has the shift started yet?
                                    if (now('Asia/Manila')->gt($shiftStart)) {
                                        // Should be marked absent/excused
                                        $hasApprovedLeave = \Illuminate\Support\Facades\DB::table('leave_requests')
                                            ->where('employee_id', $employee->id)
                                            ->where('status', 2) // Approved
                                            ->where('start_date', '<=', $dateStr)
                                            ->where('end_date', '>=', $dateStr)
                                            ->exists();
                                            
                                        $expectedStatus = $hasApprovedLeave ? 4 : 3; // 4 = On Leave, 3 = Absent
                                        $expectedNotes = $hasApprovedLeave ? 'Auto-marked: Approved Leave' : 'Auto-marked absent: no time-in by shift start.';
                                        
                                        if ($existingRecord) {
                                            // If it's an auto-generated record (no check-in/out), sync it
                                            if (is_null($existingRecord->check_in) && is_null($existingRecord->check_out) && in_array($existingRecord->status, [3, 4])) {
                                                if ($existingRecord->shift_id != $dayShift->id || $existingRecord->status != $expectedStatus) {
                                                    $existingRecord->update([
                                                        'shift_id' => $dayShift->id,
                                                        'status' => $expectedStatus,
                                                        'notes' => $expectedNotes,
                                                    ]);
                                                }
                                            }
                                        } else {
                                            // Create new auto-absent record
                                            \App\Models\Attendance::create([
                                                'user_id' => $user->id,
                                                'shift_id' => $dayShift->id,
                                                'attendance_date' => $dateStr,
                                                'status' => $expectedStatus,
                                                'notes' => $expectedNotes,
                                            ]);
                                        }
                                    } else {
                                        // Shift has not started yet (e.g. shift was moved to future or rescheduled)
                                        // Delete any existing auto-generated record
                                        if ($existingRecord && is_null($existingRecord->check_in) && is_null($existingRecord->check_out) && in_array($existingRecord->status, [3, 4])) {
                                            $existingRecord->delete();
                                        }
                                    }
                                } else {
                                    // Not scheduled to work today
                                    // Delete any existing auto-generated record
                                    if ($existingRecord && is_null($existingRecord->check_in) && is_null($existingRecord->check_out) && in_array($existingRecord->status, [3, 4])) {
                                        $existingRecord->delete();
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Silently ignore to avoid breaking early migrations/installs
            }
        }
    }
}
