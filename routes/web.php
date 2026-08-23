<?php

use App\Http\Controllers\Admin\AdminLoanApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialCalculatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::post('/api/calculator/compute', [FinancialCalculatorController::class, 'compute'])
    ->name('calculator.compute');

// Admin authentication.
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// Admin area (authenticated + admin-role only).
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminLoanApplicationController::class, 'index'])->name('dashboard');
    Route::get('/applications', [AdminLoanApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{loanApplication}', [AdminLoanApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{loanApplication}/edit', [AdminLoanApplicationController::class, 'edit'])->name('applications.edit');
    Route::put('/applications/{loanApplication}', [AdminLoanApplicationController::class, 'update'])->name('applications.update');
    Route::delete('/applications/{loanApplication}', [AdminLoanApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::post('/applications/{loanApplication}/reassess', [AdminLoanApplicationController::class, 'reassess'])->name('applications.reassess');
});