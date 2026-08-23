<?php

use App\Http\Controllers\LoanApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Registered here and automatically loaded by the RouteServiceProvider
| with the "api" middleware group and "/api" prefix.
|
*/

Route::prefix('v1')->name('v1.')->group(function () {
    Route::get('loan-applications', [LoanApplicationController::class, 'index'])
        ->name('loan-applications.index');
    Route::post('loan-applications', [LoanApplicationController::class, 'store'])
        ->name('loan-applications.store');
    Route::get('loan-applications/{loanApplication}', [LoanApplicationController::class, 'show'])
        ->name('loan-applications.show');
});