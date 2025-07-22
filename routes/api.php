<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Daily Sales routes
Route::get('daily-sales/month/{year?}/{month?}', [\App\Http\Controllers\Api\DailySaleController::class, 'getByMonth'])->middleware('auth:sanctum');
Route::apiResource('daily-sales', \App\Http\Controllers\Api\DailySaleController::class)->middleware('auth:sanctum');

// Daily Fuels routes
Route::get('daily-fuels/month/{year?}/{month?}', [\App\Http\Controllers\Api\DailyFuelController::class, 'getByMonth'])->middleware('auth:sanctum');
Route::apiResource('daily-fuels', \App\Http\Controllers\Api\DailyFuelController::class)->middleware('auth:sanctum');

// Vendor routes
Route::apiResource('vendors', \App\Http\Controllers\Api\VendorController::class)->middleware('auth:sanctum');

// User management routes (Admin only)
Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->middleware('auth:sanctum');
Route::get('/user/profile', [\App\Http\Controllers\Api\UserController::class, 'profile'])->middleware('auth:sanctum');

// Employee routes
Route::get('/employees/stats', [\App\Http\Controllers\Api\EmployeeController::class, 'stats'])->middleware('auth:sanctum');
Route::get('/employees/earnings', [\App\Http\Controllers\Api\EmployeeController::class, 'earnings'])->middleware('auth:sanctum');
Route::get('/employees/pay-days', [\App\Http\Controllers\Api\EmployeeController::class, 'getPayDays'])->middleware('auth:sanctum');
Route::post('/employees/work-hour-report', [\App\Http\Controllers\Api\EmployeeController::class, 'generateWorkHourReport'])->middleware('auth:sanctum');
Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class)->middleware('auth:sanctum');

// Work Hours routes
Route::apiResource('work-hours', \App\Http\Controllers\Api\WorkHourController::class)->middleware('auth:sanctum');
Route::get('/work-hours/recent', [\App\Http\Controllers\Api\WorkHourController::class, 'recent'])->middleware('auth:sanctum');
Route::get('/work-hours/summary', [\App\Http\Controllers\Api\WorkHourController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/employees/{employee}/work-hours', [\App\Http\Controllers\Api\WorkHourController::class, 'employeeHours'])->middleware('auth:sanctum'); 