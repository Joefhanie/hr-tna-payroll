<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\TimekeepingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/timekeeping', [TimekeepingController::class, 'index'])->name('timekeeping.index');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::view('/onboarding', 'onboarding')->name('onboarding');
    Route::view('/leave', 'leave')->name('leave');
    Route::view('/benefits', 'benefits')->name('benefits');
    Route::view('/self-service', 'self-service')->name('self-service');
    Route::view('/reports', 'reports')->name('reports');

    Route::redirect('/organization', '/organization/departments');
    Route::get('/organization/departments', [OrganizationController::class, 'departments'])->name('organization.departments.index');
    Route::get('/organization/departments/{department}', [OrganizationController::class, 'showDepartment'])->name('organization.departments.show');
    Route::get('/organization/departments/{department}/edit', [OrganizationController::class, 'editDepartment'])->name('organization.departments.edit');
    Route::post('/organization/departments', [OrganizationController::class, 'storeDepartment'])->name('organization.departments.store');
    Route::put('/organization/departments/{department}', [OrganizationController::class, 'updateDepartment'])->name('organization.departments.update');
    Route::delete('/organization/departments/{department}', [OrganizationController::class, 'destroyDepartment'])->name('organization.departments.destroy');

    Route::get('/organization/positions', [OrganizationController::class, 'positions'])->name('organization.positions.index');
    Route::get('/organization/positions/{position}', [OrganizationController::class, 'showPosition'])->name('organization.positions.show');
    Route::get('/organization/positions/{position}/edit', [OrganizationController::class, 'editPosition'])->name('organization.positions.edit');
    Route::post('/organization/positions', [OrganizationController::class, 'storePosition'])->name('organization.positions.store');
    Route::put('/organization/positions/{position}', [OrganizationController::class, 'updatePosition'])->name('organization.positions.update');
    Route::delete('/organization/positions/{position}', [OrganizationController::class, 'destroyPosition'])->name('organization.positions.destroy');

    // Employee Management
    Route::resource('employees', EmployeeController::class);

    // Salary Management
    Route::get('/salaries', [SalaryController::class, 'index'])->name('salary.index');
    Route::get('/salaries/settings', [SalaryController::class, 'settings'])->name('salary.settings');
    Route::post('/salaries/settings/tax-brackets', [SalaryController::class, 'saveTaxBrackets'])->name('salary.save-tax-brackets');
    Route::post('/salaries/settings/government-contributions', [SalaryController::class, 'saveGovernmentContributions'])->name('salary.save-government-contributions');
    Route::post('/salaries/settings/deduction-rules', [SalaryController::class, 'saveDeductionRules'])->name('salary.save-deduction-rules');
    Route::get('/employees/{employee}/salary/create', [SalaryController::class, 'create'])->name('salary.create');
    Route::post('/employees/{employee}/salary', [SalaryController::class, 'store'])->name('salary.store');
    Route::get('/employees/{employee}/salary', [SalaryController::class, 'show'])->name('salary.show');
    Route::get('/salary/{salaryRecord}/edit', [SalaryController::class, 'edit'])->name('salary.edit');
    Route::put('/salary/{salaryRecord}', [SalaryController::class, 'update'])->name('salary.update');
    Route::delete('/salary/{salaryRecord}', [SalaryController::class, 'destroy'])->name('salary.destroy');
    Route::post('/employees/{employee}/salary/assignments', [SalaryController::class, 'saveAssignments'])->name('salary.save-assignments');

    // Payroll Management
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll/run', [PayrollController::class, 'run'])->name('payroll.run');
    Route::get('/payroll/{payRun}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/payroll/{payRun}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
    Route::get('/payroll/{payRun}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::put('/payroll/{payRun}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('/payroll/{payRun}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
});
