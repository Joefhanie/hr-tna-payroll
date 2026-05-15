<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayrollController;
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
    Route::view('/timekeeping', 'timekeeping')->name('timekeeping');
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

    // Payroll Management
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
});
