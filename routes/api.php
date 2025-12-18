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