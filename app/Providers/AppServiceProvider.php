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
                }
            } catch (\Throwable $e) {
                // Silently ignore to avoid breaking early migrations/installs
            }
        }
    }
}
