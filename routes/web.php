<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\TimekeepingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/register/profile', [RegisterController::class, 'profile'])->name('register.profile');
    Route::post('/register/profile', [RegisterController::class, 'storeProfile'])->name('register.profile.store');
    Route::get('/register/employment', [RegisterController::class, 'employment'])->name('register.employment');
    Route::post('/register/employment', [RegisterController::class, 'storeEmployment'])->name('register.employment.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/self-service', [SelfServiceController::class, 'index'])->name('self-service');
    Route::get('/self-service/profile/{employee}', [SelfServiceController::class, 'profile'])->name('self-service.profile');
    Route::post('/self-service/profile/{employee}/leave-requests', [SelfServiceController::class, 'storeLeaveRequest'])->name('self-service.leave-requests.store');
    Route::post('/self-service/profile/{employee}/profile-update-requests', [SelfServiceController::class, 'storeProfileUpdateRequest'])->name('self-service.profile-update-requests.store');
    Route::post('/self-service/profile/{employee}/documents', [SelfServiceController::class, 'storeDocumentUpload'])->name('self-service.documents.store');

    // Timekeeping Management
    Route::middleware('permission:timekeeping.view,timekeeping.create,timekeeping.edit,timekeeping.delete')->group(function () {
        Route::get('/timekeeping', [TimekeepingController::class, 'index'])->name('timekeeping.index');
        Route::get('/timekeeping/shift-schedule', [TimekeepingController::class, 'shiftSchedule'])->name('timekeeping.shift-schedule');
        Route::get('/timekeeping/{user}', [TimekeepingController::class, 'show'])->name('timekeeping.show');
    });
    Route::middleware('permission:timekeeping.create,timekeeping.edit')->group(function () {
        Route::post('/timekeeping/manual', [TimekeepingController::class, 'storeManual'])->name('timekeeping.manual.store');
        Route::post('/timekeeping/shift-schedule/save', [TimekeepingController::class, 'saveShiftSchedule'])->name('timekeeping.shift-schedule.save');
    });

    // Onboarding, Leave, Benefits, Reports
    Route::view('/onboarding', 'onboarding')->name('onboarding')->middleware('permission:employees.view,employees.create,employees.edit,employees.delete');
    Route::view('/leave', 'leave')->name('leave')->middleware('permission:leaves.view,leaves.create,leaves.edit,leaves.delete');
    Route::view('/benefits', 'benefits')->name('benefits')->middleware('permission:benefits.view,benefits.create,benefits.edit,benefits.delete');
    Route::view('/reports', 'reports')->name('reports')->middleware('permission:reports.view,reports.create,reports.edit,reports.delete');

    // Organization Management (Settings)
    Route::middleware('permission:settings.view,settings.create,settings.edit,settings.delete')->group(function () {
        Route::redirect('/organization', '/organization/departments');
        Route::get('/organization/departments', [OrganizationController::class, 'departments'])->name('organization.departments.index');
        Route::get('/organization/departments/{department}', [OrganizationController::class, 'showDepartment'])->name('organization.departments.show');
        Route::get('/organization/positions', [OrganizationController::class, 'positions'])->name('organization.positions.index');
        Route::get('/organization/positions/{position}', [OrganizationController::class, 'showPosition'])->name('organization.positions.show');
        Route::get('/organization/users', [OrganizationController::class, 'users'])->name('organization.users.index');
        Route::get('/organization/settings', [OrganizationController::class, 'settings'])->name('organization.settings');
    });
    Route::middleware('permission:settings.create')->group(function () {
        Route::post('/organization/departments', [OrganizationController::class, 'storeDepartment'])->name('organization.departments.store');
        Route::post('/organization/positions', [OrganizationController::class, 'storePosition'])->name('organization.positions.store');
        Route::post('/organization/users', [OrganizationController::class, 'storeUser'])->name('organization.users.store');
    });
    Route::middleware('permission:settings.edit')->group(function () {
        Route::get('/organization/departments/{department}/edit', [OrganizationController::class, 'editDepartment'])->name('organization.departments.edit');
        Route::put('/organization/departments/{department}', [OrganizationController::class, 'updateDepartment'])->name('organization.departments.update');
        Route::get('/organization/positions/{position}/edit', [OrganizationController::class, 'editPosition'])->name('organization.positions.edit');
        Route::put('/organization/positions/{position}', [OrganizationController::class, 'updatePosition'])->name('organization.positions.update');
        Route::get('/organization/users/{user}/edit', [OrganizationController::class, 'editUser'])->name('organization.users.edit');
        Route::put('/organization/users/{user}', [OrganizationController::class, 'updateUser'])->name('organization.users.update');
        Route::put('/organization/users/{user}/permissions', [OrganizationController::class, 'updateUserPermissions'])->name('organization.users.permissions.update');
        Route::post('/organization/settings', [OrganizationController::class, 'updateSettings'])->name('organization.settings.update');
    });
    Route::middleware('permission:settings.delete')->group(function () {
        Route::delete('/organization/departments/{department}', [OrganizationController::class, 'destroyDepartment'])->name('organization.departments.destroy');
        Route::delete('/organization/positions/{position}', [OrganizationController::class, 'destroyPosition'])->name('organization.positions.destroy');
    });

    // Employee Management
    Route::middleware('permission:employees.view,employees.create,employees.edit,employees.delete')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees-temporary-access', [EmployeeController::class, 'temporaryAccess'])->name('employees.temporary-access');
    });
    Route::middleware('permission:employees.create')->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    });
    Route::middleware('permission:employees.edit')->group(function () {
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::get('/employees/{employee}/temporary-access', [EmployeeController::class, 'showTemporaryAccess'])->name('employees.temporary-access.show');
    Route::patch('/employees/{employee}/grant-role', [EmployeeController::class, 'grantRole'])->name('employees.grant-role');
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
        Route::get('/salaries', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/employees/{employee}/salary', [SalaryController::class, 'show'])->name('salary.show');
        
        Route::get('/salaries/settings', [SalaryController::class, 'settings'])->name('salary.settings');

        Route::redirect('/payroll/special-case', '/payroll/plotting-payment');
        Route::redirect('/payroll/plotting-of-payments', '/payroll/plotting-payment');
        Route::redirect('/payroll/plotting-payments', '/payroll/plotting-payment');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/plotting-payment', [PayrollController::class, 'plottingPayment'])->name('payroll.plotting-payment');
        Route::get('/payroll/plotting-payment/{employee}', [PayrollController::class, 'showPlottingEmployee'])->name('payroll.plotting-payment.employee');
        Route::get('/payroll/work-location/{date}/{workplace}', [PayrollController::class, 'showWorkLocationDetails'])->name('payroll.work-location-details');
        Route::get('/payroll/per-date/{date}', [PayrollController::class, 'showPerDateDetails'])->name('payroll.per-date');
        Route::get('/payroll/{payRun}', [PayrollController::class, 'show'])->name('payroll.show');
    });
    Route::middleware('permission:payroll.edit')->group(function () {
        Route::post('/salaries/settings/tax-brackets', [SalaryController::class, 'saveTaxBrackets'])->name('salary.save-tax-brackets');
        Route::post('/salaries/settings/late-deduction-rules', [SalaryController::class, 'saveLateDeductionRules'])->name('salary.save-late-deduction-rules');
        Route::post('/salaries/settings/government-contributions', [SalaryController::class, 'saveGovernmentContributions'])->name('salary.save-government-contributions');
        Route::post('/salaries/settings/deduction-rules', [SalaryController::class, 'saveDeductionRules'])->name('salary.save-deduction-rules');
        Route::post('/salaries/settings/payroll', [SalaryController::class, 'savePayrollSettings'])->name('salary.save-payroll-settings');

        Route::get('/salary/{salaryRecord}/edit', [SalaryController::class, 'edit'])->name('salary.edit');
        Route::put('/salary/{salaryRecord}', [SalaryController::class, 'update'])->name('salary.update');
        Route::post('/employees/{employee}/salary/assignments', [SalaryController::class, 'saveAssignments'])->name('salary.save-assignments');

        Route::post('/payroll/{payRun}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::get('/payroll/{payRun}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
        Route::put('/payroll/{payRun}', [PayrollController::class, 'update'])->name('payroll.update');
        Route::post('/payroll/plotting-payment/save', [PayrollController::class, 'savePlottingPayment'])->name('payroll.plotting-payment.save');
        Route::post('/payroll/plotting-payment/{employee}', [PayrollController::class, 'savePlottingEmployee'])->name('payroll.plotting-payment.employee.save');
    });
    Route::middleware('permission:payroll.delete')->group(function () {
        Route::delete('/salary/{salaryRecord}', [SalaryController::class, 'destroy'])->name('salary.destroy');
        Route::delete('/payroll/{payRun}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    });
});
