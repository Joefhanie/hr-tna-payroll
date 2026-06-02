<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipDisputeController;
use App\Http\Controllers\PreviousClaimController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\TimekeepingController;
use App\Models\Employee;
use App\Models\Masterlist;
use App\Models\TapRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use App\Services\TapRecordAttendanceService;

Route::redirect('/', 'dashboard');

// Machine-independent media URL for files stored on the public disk.
Route::get('/media/{path}', [PublicStorageController::class, 'show'])
    ->where('path', '.*')
    ->name('media.file');

Route::middleware('guest')->group(function () {
    // Route::get('/register', [RegisterController::class, 'create'])->name('register');
    // Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    // Route::get('/register/profile', [RegisterController::class, 'profile'])->name('register.profile');
    // Route::post('/register/profile', [RegisterController::class, 'storeProfile'])->name('register.profile.store');
    // Route::get('/register/employment', [RegisterController::class, 'employment'])->name('register.employment');
    // Route::post('/register/employment', [RegisterController::class, 'storeEmployment'])->name('register.employment.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // User Profile
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('permission:self-service.view')->group(function () {
        Route::get('/self-service', [SelfServiceController::class, 'index'])->name('self-service');
        Route::get('/self-service/profile/{employee}', [SelfServiceController::class, 'profile'])->name('self-service.profile');
    });

    Route::middleware('permission:self-service.create')->group(function () {
        Route::post('/self-service/profile/{employee}/leave-requests', [SelfServiceController::class, 'storeLeaveRequest'])->name('self-service.leave-requests.store');
        Route::post('/self-service/profile/{employee}/claim-requests', [SelfServiceController::class, 'storeClaimRequest'])->name('self-service.claim-requests.store');
        Route::post('/self-service/profile/{employee}/profile-update-requests', [SelfServiceController::class, 'storeProfileUpdateRequest'])->name('self-service.profile-update-requests.store');
        Route::post('/self-service/profile/{employee}/profile-picture', [SelfServiceController::class, 'storeProfilePicture'])->name('self-service.profile-picture.store');
        Route::post('/self-service/profile/{employee}/documents', [SelfServiceController::class, 'storeDocumentUpload'])->name('self-service.documents.store');
    });

    Route::middleware('permission:self-service.edit')->group(function () {
        Route::post('/self-service/profile-update-requests/{profileUpdateRequest}/review', [SelfServiceController::class, 'reviewProfileUpdateRequest'])->name('self-service.profile-update-requests.review');
    });

    // Timekeeping Management
    Route::middleware('permission:timekeeping.view,timekeeping.create,timekeeping.edit,timekeeping.delete')->group(function () {
        Route::get('/timekeeping/export', [TimekeepingController::class, 'export'])->name('timekeeping.export');
        Route::get('/timekeeping', [TimekeepingController::class, 'index'])->name('timekeeping.index');
        Route::get('/timekeeping/shift-schedule/export', [TimekeepingController::class, 'exportShiftSchedule'])->name('timekeeping.shift-schedule.export');
        Route::get('/timekeeping/shift-schedule', [TimekeepingController::class, 'shiftSchedule'])->name('timekeeping.shift-schedule');
        Route::get('/timekeeping/{user}', [TimekeepingController::class, 'show'])->name('timekeeping.show');
    });
    Route::get('/tap-records', function () {
        abort_unless(Schema::hasTable('tap_records'), 404);

        $employees = Employee::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'masterlist_id']);

        $tapRecords = DB::table('tap_records as tap')
            ->leftJoin('employees as employee_by_id', 'tap.employee_id', '=', 'employee_by_id.id')
            ->leftJoin('masterlist as masterlist_by_id', 'tap.masterlist_id', '=', 'masterlist_by_id.id')
            ->leftJoin('employees as employee_by_masterlist', 'masterlist_by_id.emp_id', '=', 'employee_by_masterlist.id')
            ->leftJoin('users as creator', 'tap.created_by', '=', 'creator.id')
            ->leftJoin('users as updater', 'tap.updated_by', '=', 'updater.id')
            ->leftJoin('users as deleter', 'tap.deleted_by', '=', 'deleter.id')
            ->select([
                'tap.id',
                'tap.employee_id',
                'tap.masterlist_id',
                'masterlist_by_id.emp_id as masterlist_emp_id',
                'tap.machine_id',
                'tap.time',
                'tap.function',
                'tap.status',
                'tap.created_at',
                'tap.created_by',
                'tap.updated_at',
                'tap.updated_by',
                'tap.deleted_at',
                'tap.deleted_by',
                'employee_by_id.employee_code as employee_code_by_id',
                'employee_by_id.first_name as employee_first_name_by_id',
                'employee_by_id.middle_name as employee_middle_name_by_id',
                'employee_by_id.last_name as employee_last_name_by_id',
                'employee_by_masterlist.employee_code as employee_code_by_masterlist',
                'employee_by_masterlist.first_name as employee_first_name_by_masterlist',
                'employee_by_masterlist.middle_name as employee_middle_name_by_masterlist',
                'employee_by_masterlist.last_name as employee_last_name_by_masterlist',
                'creator.name as created_by_name',
                'updater.name as updated_by_name',
                'deleter.name as deleted_by_name',
            ])
            ->orderByDesc('tap.id')
            ->paginate(25);

        $tapRecords->getCollection()->transform(function ($record) {
            $employeeFirst = $record->employee_first_name_by_masterlist ?? $record->employee_first_name_by_id;
            $employeeMiddle = $record->employee_middle_name_by_masterlist ?? $record->employee_middle_name_by_id;
            $employeeLast = $record->employee_last_name_by_masterlist ?? $record->employee_last_name_by_id;

            $employeeParts = array_filter([
                $employeeFirst,
                $employeeMiddle ? strtoupper(substr((string) $employeeMiddle, 0, 1)) . '.' : null,
                $employeeLast,
            ]);

            $record->employee_label = $employeeParts
                ? trim(implode(' ', $employeeParts))
                : ($record->masterlist_id ? 'Masterlist #' . $record->masterlist_id : ($record->employee_id ? 'Employee #' . $record->employee_id : 'Unlinked record'));

            $record->employee_code_label = $record->employee_code_by_masterlist
                ?? $record->employee_code_by_id
                ?? 'N/A';

            $record->created_by_label = $record->created_by_name
                ?? ($record->created_by ? 'User #' . $record->created_by : 'System');

            $record->updated_by_label = $record->updated_by_name
                ?? ($record->updated_by ? 'User #' . $record->updated_by : '—');

            $record->deleted_by_label = $record->deleted_by_name
                ?? ($record->deleted_by ? 'User #' . $record->deleted_by : '—');

            $record->time_label = $record->time ? Carbon::parse($record->time)->format('Y-m-d H:i:s') : 'N/A';

            $functionMap = [
                1 => 'Check In',
                2 => 'Check Out',
                3 => 'Break In',
                4 => 'Break Out',
            ];
            $funcCode = (int) $record->function;
            $record->function_label = isset($functionMap[$funcCode])
                ? $functionMap[$funcCode] . ' (' . $funcCode . ')'
                : 'Function #' . $funcCode;
            $record->status_label = 'Status #' . (string) $record->status;

            return $record;
        });

        return view('tap-records.index', compact('employees', 'tapRecords'));
    })->name('tap-records.index');
    Route::post('/tap-records', function (Request $request) {
        abort_unless(Schema::hasTable('tap_records'), 404);

        $validated = $request->validate([
            'masterlist_id' => 'required|exists:masterlist,id',
            'machine_id' => 'required|integer|min:1',
            'tap_time' => 'required|date',
            'function' => 'required|integer|min:0',
            'status' => 'required|integer|min:0',
        ]);

        $masterlist = Masterlist::with('employee')->findOrFail($validated['masterlist_id']);
        $employee = $masterlist->employee;

        if (!$employee) {
            return back()->withErrors([
                'masterlist_id' => 'Selected masterlist record is not linked to an employee.',
            ])->withInput();
        }

        $tapTime = Carbon::parse($validated['tap_time']);

        // Use Eloquent create so the TapRecord model's created hook triggers
        TapRecord::create([
            'employee_id' => $employee->id,
            'masterlist_id' => $masterlist->id,
            'machine_id' => $validated['machine_id'],
            'time' => $tapTime->toDateTimeString(),
            'function' => $validated['function'],
            'status' => $validated['status'],
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => null,
            'updated_by' => null,
            'deleted_at' => null,
            'deleted_by' => null,
        ]);

        return redirect()->route('tap-records.index')->with('success', 'Tap record added successfully.');
    })->name('tap-records.store');
    Route::post('/tap-records/sync', function () {
        abort_unless(Schema::hasTable('tap_records'), 404);

        $synced = app(TapRecordAttendanceService::class)->syncAll();

        return redirect()->route('tap-records.index')->with('success', $synced . ' attendance record(s) synced from tap records.');
    })->name('tap-records.sync');
    Route::middleware('permission:timekeeping.create,timekeeping.edit')->group(function () {
        Route::post('/timekeeping/manual', [TimekeepingController::class, 'storeManual'])->name('timekeeping.manual.store');
        Route::post('/timekeeping/shift-schedule/save', [TimekeepingController::class, 'saveShiftSchedule'])->name('timekeeping.shift-schedule.save');
    });
    Route::middleware('permission:timekeeping.delete')->group(function () {
        Route::post('/timekeeping/shift-schedule/soft-delete', [TimekeepingController::class, 'softDeleteShiftSchedule'])->name('timekeeping.shift-schedule.soft-delete');
    });

    // Onboarding, Leave, Benefits, Reports
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
    Route::get('/onboarding/company-documents/{companyDocument}/download', [OnboardingController::class, 'downloadCompanyDocument'])->name('onboarding.company-documents.download');
    Route::post('/onboarding/{employee}/start', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::post('/onboarding/{employee}/tasks', [OnboardingController::class, 'storeTask'])->name('onboarding.tasks.store');
    Route::put('/onboarding/tasks/{task}', [OnboardingController::class, 'updateTask'])->name('onboarding.tasks.update');
    Route::delete('/onboarding/tasks/{task}', [OnboardingController::class, 'destroyTask'])->name('onboarding.tasks.destroy');
    Route::post('/onboarding/tasks/{task}/submit', [OnboardingController::class, 'submitEmployeeTask'])->name('onboarding.tasks.submit');
    Route::post('/onboarding/tasks/{task}/complete', [OnboardingController::class, 'completeTask'])->name('onboarding.tasks.complete');
    Route::view('/leave', 'leave')->name('leave')->middleware('permission:leaves.view');
    // Benefits Management
    Route::middleware('permission:benefits.view,benefits.create,benefits.edit,benefits.delete')->group(function () {
        Route::get('/benefits', [App\Http\Controllers\BenefitsController::class, 'index'])->name('benefits');
        Route::get('/benefits/{plan}', [App\Http\Controllers\BenefitsController::class, 'show'])->name('benefits.show');
    });
    Route::middleware('permission:benefits.create')->group(function () {
        Route::post('/benefits', [App\Http\Controllers\BenefitsController::class, 'store'])->name('benefits.store');
    });
    Route::middleware('permission:benefits.edit')->group(function () {
        Route::put('/benefits/{plan}', [App\Http\Controllers\BenefitsController::class, 'update'])->name('benefits.update');
        Route::post('/benefits/{plan}/enroll', [App\Http\Controllers\BenefitsController::class, 'enroll'])->name('benefits.enroll');
        Route::post('/benefits/{plan}/disenroll/{employee}', [App\Http\Controllers\BenefitsController::class, 'disenroll'])->name('benefits.disenroll');
    });
    Route::middleware('permission:benefits.delete')->group(function () {
        Route::delete('/benefits/{plan}', [App\Http\Controllers\BenefitsController::class, 'destroy'])->name('benefits.destroy');
    });
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports')->middleware('permission:reports.view,reports.create,reports.edit,reports.delete');
    Route::get('/reports/download/{type}', [App\Http\Controllers\ReportController::class, 'download'])->name('reports.download')->middleware('permission:reports.view,reports.create,reports.edit,reports.delete');

    // Leave Management
    Route::middleware('permission:leaves.view,leaves.create,leaves.edit,leaves.delete')->group(function () {
        Route::get('/leave/export', [LeaveController::class, 'export'])->name('leave.export');
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('/leave/calendar', [LeaveController::class, 'calendarView'])->name('leave.calendar');
    });
    Route::middleware('permission:leaves.create')->group(function () {
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::post('/leave/types', [LeaveController::class, 'storeType'])->name('leave.types.store');
    });
    Route::middleware('permission:leaves.edit')->group(function () {
        Route::post('/leave/{leave}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leave}/decline', [LeaveController::class, 'decline'])->name('leave.decline');
        Route::post('/leave/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    });

    // Organization Management (Settings)
    Route::middleware('permission:settings.view,settings.create,settings.edit,settings.delete')->group(function () {
        Route::redirect('/organization', '/organization/departments');
        Route::get('/organization/departments', [OrganizationController::class, 'departments'])->name('organization.departments.index');
        Route::get('/organization/departments/{department}', [OrganizationController::class, 'showDepartment'])->name('organization.departments.show');
        Route::get('/organization/positions', [OrganizationController::class, 'positions'])->name('organization.positions.index');
        Route::get('/organization/positions/{position}', [OrganizationController::class, 'showPosition'])->name('organization.positions.show');
        Route::get('/organization/users', [OrganizationController::class, 'users'])->name('organization.users.index');
        Route::get('/organization/settings', [OrganizationController::class, 'settings'])->name('organization.settings');
        Route::get('/organization/settings/company-documents/{companyDocument}/download', [OrganizationController::class, 'downloadCompanyDocument'])->name('organization.settings.company-documents.download');
        Route::get('/organization/settings/machine-settings', [OrganizationController::class, 'machineSettings'])->name('organization.settings.machine-settings');
    });
    Route::middleware('permission:settings.create')->group(function () {
        Route::post('/organization/departments', [OrganizationController::class, 'storeDepartment'])->name('organization.departments.store');
        Route::post('/organization/positions', [OrganizationController::class, 'storePosition'])->name('organization.positions.store');
        Route::post('/organization/users', [OrganizationController::class, 'storeUser'])->name('organization.users.store');
        Route::post('/organization/settings/company-documents', [OrganizationController::class, 'storeCompanyDocument'])->name('organization.settings.company-documents.store');
        Route::post('/organization/settings/machine-settings', [OrganizationController::class, 'storeMachineSetting'])->name('organization.settings.machine-settings.store');
    });
    Route::middleware('permission:settings.edit')->group(function () {
        Route::get('/organization/departments/{department}/edit', [OrganizationController::class, 'editDepartment'])->name('organization.departments.edit');
        Route::put('/organization/departments/{department}', [OrganizationController::class, 'updateDepartment'])->name('organization.departments.update');
        Route::get('/organization/positions/{position}/edit', [OrganizationController::class, 'editPosition'])->name('organization.positions.edit');
        Route::put('/organization/positions/{position}', [OrganizationController::class, 'updatePosition'])->name('organization.positions.update');
        Route::get('/organization/users/{user}/edit', [OrganizationController::class, 'editUser'])->name('organization.users.edit');
        Route::put('/organization/users/{user}', [OrganizationController::class, 'updateUser'])->name('organization.users.update');
        Route::match(['post', 'put'], '/organization/users/{user}/permissions', [OrganizationController::class, 'updateUserPermissions'])->name('organization.users.permissions.update');
        Route::post('/organization/settings', [OrganizationController::class, 'updateSettings'])->name('organization.settings.update');
        Route::post('/organization/settings/reset-brand-colors', [OrganizationController::class, 'resetBrandColors'])->name('organization.settings.reset-brand-colors');
    });
    Route::middleware('permission:settings.delete')->group(function () {
        Route::delete('/organization/departments/{department}', [OrganizationController::class, 'destroyDepartment'])->name('organization.departments.destroy');
        Route::delete('/organization/positions/{position}', [OrganizationController::class, 'destroyPosition'])->name('organization.positions.destroy');
        Route::delete('/organization/settings/machine-settings/{machineSetting}', [OrganizationController::class, 'destroyMachineSetting'])->name('organization.settings.machine-settings.destroy');
    });

    // Employee Management
    Route::middleware('permission:employees.create')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    });
    Route::middleware('permission:employees.view,employees.create,employees.edit,employees.delete')->group(function () {
        Route::get('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees-temporary-access', [EmployeeController::class, 'temporaryAccess'])->name('employees.temporary-access');
        Route::get('/employees-temporary-access/export', [EmployeeController::class, 'exportTemporaryAccess'])->name('employees.temporary-access.export');
    });
    Route::middleware('permission:employees.edit')->group(function () {
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::get('/employees/{employee}/temporary-access', [EmployeeController::class, 'showTemporaryAccess'])->name('employees.temporary-access.show');
        Route::match(['post', 'patch'], '/employees/{employee}/grant-role', [EmployeeController::class, 'grantRole'])->name('employees.grant-role');
        Route::post('/employees/{employee}/revoke-role', [EmployeeController::class, 'revokeRole'])->name('employees.revoke-role');
    });
    Route::middleware('permission:employees.delete')->group(function () {
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('/employees/{employee}/terminate', [EmployeeController::class, 'terminate'])->name('employees.terminate');
    });

    // Salary & Payroll Management
    Route::middleware('permission:payroll.create')->group(function () {
        Route::get('/employees/{employee}/salary/create', [SalaryController::class, 'create'])->name('salary.create');
        Route::post('/employees/{employee}/salary', [SalaryController::class, 'store'])->name('salary.store');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    });
    Route::middleware('permission:payroll.view,payroll.create,payroll.edit,payroll.delete')->group(function () {
        Route::get('/salaries/export', [SalaryController::class, 'export'])->name('salary.export');
        Route::get('/salaries', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/employees/{employee}/salary', [SalaryController::class, 'show'])->name('salary.show');

        Route::get('/salaries/settings', [SalaryController::class, 'settings'])->name('salary.settings');
        Route::get('/salaries/government-premiums', [SalaryController::class, 'governmentPremiums'])->name('salary.government-premiums');
        Route::get('/salaries/contribution-tables', [SalaryController::class, 'contributionTables'])->name('salary.contribution-tables');

        Route::redirect('/payroll/special-case', '/payroll/plotting-payment');
        Route::redirect('/payroll/plotting-of-payments', '/payroll/plotting-payment');
        Route::redirect('/payroll/plotting-payments', '/payroll/plotting-payment');
        Route::get('/payroll/export', [PayrollController::class, 'export'])->name('payroll.export');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/plotting-payment', [PayrollController::class, 'plottingPayment'])->name('payroll.plotting-payment');
        Route::get('/payroll/plotting-payment/missed', [PayrollController::class, 'findMissedPlottings'])->name('payroll.plotting-payment.missed');
        Route::get('/payroll/plotting-payment/{employee}', [PayrollController::class, 'showPlottingEmployee'])->name('payroll.plotting-payment.employee');
        Route::get('/payroll/work-location/{date}/{workplace}', [PayrollController::class, 'showWorkLocationDetails'])->name('payroll.work-location-details');
        Route::get('/payroll/per-date/{date}', [PayrollController::class, 'showPerDateDetails'])->name('payroll.per-date');

        // Previous Claims — must be BEFORE the {payRun} wildcard
        Route::get('/payroll/previous-claims/export', [PreviousClaimController::class, 'export'])->name('payroll.previous-claims.export');
        Route::get('/payroll/previous-claims', [PreviousClaimController::class, 'index'])->name('payroll.previous-claims.index');
        Route::post('/payroll/previous-claims', [PreviousClaimController::class, 'store'])->name('payroll.previous-claims.store');

        // Disputes — must be BEFORE the {payRun} wildcard
        Route::get('/payroll/disputes/export', [PayslipDisputeController::class, 'export'])->name('payroll.disputes.export');
        Route::get('/payroll/disputes', [PayslipDisputeController::class, 'index'])->name('payroll.disputes.index');
        Route::post('/payroll/disputes', [PayslipDisputeController::class, 'store'])->name('payroll.disputes.store');
        Route::get('/payroll/disputes/api/payslip-items/{payslip}', [PayslipDisputeController::class, 'getPayslipItems'])->name('payroll.disputes.api.payslip-items');
        Route::get('/payroll/disputes/api/payslips/{employeeId}', [PayslipDisputeController::class, 'getPayslips'])->name('payroll.disputes.api.payslips');

        Route::get('/payroll/{payRun}', [PayrollController::class, 'show'])->name('payroll.show');
    });

    // Previous Claims — HR approve / decline / delete
    Route::middleware('permission:payroll.edit')->group(function () {
        Route::post('/payroll/previous-claims/{previousClaim}/approve', [PreviousClaimController::class, 'approve'])->name('payroll.previous-claims.approve');
        Route::post('/payroll/previous-claims/{previousClaim}/decline', [PreviousClaimController::class, 'decline'])->name('payroll.previous-claims.decline');

        // Disputes — HR resolve / reject
        Route::post('/payroll/disputes/{dispute}/resolve', [PayslipDisputeController::class, 'resolve'])->name('payroll.disputes.resolve');
        Route::post('/payroll/disputes/{dispute}/reject', [PayslipDisputeController::class, 'reject'])->name('payroll.disputes.reject');
    });
    Route::middleware('permission:payroll.delete')->group(function () {
        Route::delete('/payroll/previous-claims/{previousClaim}', [PreviousClaimController::class, 'destroy'])->name('payroll.previous-claims.destroy');
    });
    Route::middleware('permission:payroll.edit')->group(function () {

        Route::post('/salaries/settings/late-deduction-rules', [SalaryController::class, 'saveLateDeductionRules'])->name('salary.save-late-deduction-rules');
        Route::post('/salaries/government-premiums', [SalaryController::class, 'saveGovernmentPremiums'])->name('salary.government-premiums.save');
        Route::post('/salaries/contribution-tables/{governmentPremium}', [SalaryController::class, 'saveContributionTable'])->name('salary.contribution-tables.save');
        Route::post('/salaries/settings/deduction-rules', [SalaryController::class, 'saveDeductionRules'])->name('salary.save-deduction-rules');
        Route::post('/salaries/settings/payroll', [SalaryController::class, 'savePayrollSettings'])->name('salary.save-payroll-settings');

        Route::get('/salary/{salaryRecord}/edit', [SalaryController::class, 'edit'])->name('salary.edit');
        Route::put('/salary/{salaryRecord}', [SalaryController::class, 'update'])->name('salary.update');
        Route::post('/employees/{employee}/salary/assignments', [SalaryController::class, 'saveAssignments'])->name('salary.save-assignments');

        Route::post('/payroll/{payRun}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::get('/payroll/{payRun}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
        Route::put('/payroll/{payRun}', [PayrollController::class, 'update'])->name('payroll.update');
        Route::post('/payroll/plotting-payment/save', [PayrollController::class, 'savePlottingPayment'])->name('payroll.plotting-payment.save');
        Route::post('/payroll/plotting-payment/save-note', [PayrollController::class, 'savePayrollNote'])->name('payroll.plotting-payment.save-note');
        Route::post('/payroll/plotting-payment/{employee}', [PayrollController::class, 'savePlottingEmployee'])->name('payroll.plotting-payment.employee.save');
    });
    Route::middleware('permission:payroll.delete')->group(function () {
        Route::delete('/salary/{salaryRecord}', [SalaryController::class, 'destroy'])->name('salary.destroy');
        Route::delete('/payroll/{payRun}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    });
});
