<?php

use App\Http\Controllers\Api\V1\ForemanController;
use App\Http\Controllers\Api\V1\WorkerController;
use App\Http\Controllers\Api\V1\UssdController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MpesaCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// M-Pesa Callbacks (no auth required)
Route::post('/mpesa/callback/stk', [MpesaCallbackController::class, 'stkCallback'])->name('mpesa.callback.stk');
Route::post('/mpesa/callback/b2c', [MpesaCallbackController::class, 'b2cCallback'])->name('mpesa.callback.b2c');

// USSD Callbacks (no auth required - Africa's Talking webhook)
Route::post('/ussd/callback', [UssdController::class, 'handleRequest'])->name('ussd.callback');

// USSD Simulation & Testing (development only)
Route::post('/ussd/simulate', [UssdController::class, 'simulate'])->name('ussd.simulate');
Route::get('/ussd/statistics', [UssdController::class, 'statistics'])->name('ussd.statistics');

// Public authentication routes
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        Route::prefix('workers/{id}')->group(function () {
            Route::get('/balance', [WorkerController::class, 'balance']);
            Route::post('/claims', [WorkerController::class, 'createClaim']);
            Route::get('/claims', [WorkerController::class, 'claims']);
            Route::get('/paycycles', [WorkerController::class, 'paycycles']);
            Route::post('/kyc', [WorkerController::class, 'uploadKyc']);
        });

        Route::prefix('sites/{site}')->group(function () {
            Route::get('/roster', [ForemanController::class, 'roster']);
            Route::post('/attendance/bulk', [ForemanController::class, 'bulkAttendance']);
            Route::get('/claims/pending', [ForemanController::class, 'pendingClaims']);
            Route::post('/claims/{claim}/approve', [ForemanController::class, 'approveClaim']);
            Route::post('/attendance/{attendanceId}/evidence', [ForemanController::class, 'uploadEvidence']);
        });
    });
});

