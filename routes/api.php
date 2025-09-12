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
Route::post('daily-sales/settlement-report', [\App\Http\Controllers\Api\DailySaleController::class, 'generateSettlementReport'])->middleware('auth:sanctum');
Route::apiResource('daily-sales', \App\Http\Controllers\Api\DailySaleController::class)->middleware('auth:sanctum');

// Daily Fuels routes
Route::get('daily-fuels/month/{year?}/{month?}', [\App\Http\Controllers\Api\DailyFuelController::class, 'getByMonth'])->middleware('auth:sanctum');
Route::apiResource('daily-fuels', \App\Http\Controllers\Api\DailyFuelController::class)->middleware('auth:sanctum');

// Fuel Volume routes
Route::get('fuel-volumes/month/{year?}/{month?}', [\App\Http\Controllers\Api\FuelVolumeController::class, 'getByMonth'])->middleware('auth:sanctum');
Route::get('fuel-volumes/daily-summary/{date?}', [\App\Http\Controllers\Api\FuelVolumeController::class, 'getDailySummary'])->middleware('auth:sanctum');
Route::apiResource('fuel-volumes', \App\Http\Controllers\Api\FuelVolumeController::class)->middleware('auth:sanctum');

// Vendor routes
Route::apiResource('vendors', \App\Http\Controllers\Api\VendorController::class)->middleware('auth:sanctum');

// Bank Account routes
Route::get('/bank-accounts/summary', [\App\Http\Controllers\Api\BankAccountController::class, 'summary'])->middleware('auth:sanctum');
Route::apiResource('bank-accounts', \App\Http\Controllers\Api\BankAccountController::class)->middleware('auth:sanctum');

// Safedrop Resolution routes
Route::get('/safedrop-resolutions/history', [\App\Http\Controllers\Api\SafedropResolutionController::class, 'history'])->middleware('auth:sanctum');
Route::apiResource('safedrop-resolutions', \App\Http\Controllers\Api\SafedropResolutionController::class)->only(['index', 'store', 'destroy'])->middleware('auth:sanctum');

// User management routes (Admin only)
Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->middleware('auth:sanctum');
Route::get('/user/profile', [\App\Http\Controllers\Api\UserController::class, 'profile'])->middleware('auth:sanctum');

// Employee routes
Route::get('/employees/stats', [\App\Http\Controllers\Api\EmployeeController::class, 'stats'])->middleware('auth:sanctum');
Route::get('/employees/earnings', [\App\Http\Controllers\Api\EmployeeController::class, 'earnings'])->middleware('auth:sanctum');
Route::get('/employees/pay-days', [\App\Http\Controllers\Api\EmployeeController::class, 'getPayDays'])->middleware('auth:sanctum');
Route::post('/employees/work-hour-report', [\App\Http\Controllers\Api\EmployeeController::class, 'generateWorkHourReport'])->middleware('auth:sanctum');
Route::post('/employees/pay-stubs', [\App\Http\Controllers\Api\EmployeeController::class, 'generatePayStubs'])->middleware('auth:sanctum');
Route::post('/employees/pay-stubs-editable', [\App\Http\Controllers\Api\EmployeeController::class, 'generatePayStubsEditable'])->middleware('auth:sanctum');
Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class)->middleware('auth:sanctum');

// Work Hours routes
Route::apiResource('work-hours', \App\Http\Controllers\Api\WorkHourController::class)->middleware('auth:sanctum');
Route::get('/work-hours/recent', [\App\Http\Controllers\Api\WorkHourController::class, 'recent'])->middleware('auth:sanctum');
Route::get('/work-hours/summary', [\App\Http\Controllers\Api\WorkHourController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/employees/{employee}/work-hours', [\App\Http\Controllers\Api\WorkHourController::class, 'employeeHours'])->middleware('auth:sanctum');

// Work Schedule routes
Route::apiResource('work-schedules', \App\Http\Controllers\Api\WorkScheduleController::class)->middleware('auth:sanctum');
Route::get('/work-schedules/current-week', [\App\Http\Controllers\Api\WorkScheduleController::class, 'currentWeekSchedules'])->middleware('auth:sanctum');
Route::get('/work-schedules/stats', [\App\Http\Controllers\Api\WorkScheduleController::class, 'stats'])->middleware('auth:sanctum');
Route::get('/work-schedules/employees-without-current-week', [\App\Http\Controllers\Api\WorkScheduleController::class, 'employeesWithoutCurrentWeekSchedule'])->middleware('auth:sanctum');
Route::get('/work-schedules/week-options', [\App\Http\Controllers\Api\WorkScheduleController::class, 'getWeekOptions'])->middleware('auth:sanctum');
Route::get('/employees/{employee}/work-schedules', [\App\Http\Controllers\Api\WorkScheduleController::class, 'employeeSchedules'])->middleware('auth:sanctum');

// Vendor Invoice routes
Route::get('/vendor-invoices/vendors', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'getVendors'])->middleware('auth:sanctum');
Route::get('/vendor-invoices/bank-accounts', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'getBankAccounts'])->middleware('auth:sanctum');
Route::get('/vendor-invoices/{vendorInvoice}/download', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'downloadFile'])->middleware('auth:sanctum');
Route::apiResource('vendor-invoices', \App\Http\Controllers\Api\VendorInvoiceController::class)->middleware('auth:sanctum');

// Provider routes
Route::apiResource('providers', \App\Http\Controllers\Api\ProviderController::class)->middleware('auth:sanctum');

// Provider Bill routes
Route::get('/provider-bills/providers', [\App\Http\Controllers\Api\ProviderBillController::class, 'getProviders'])->middleware('auth:sanctum');
Route::get('/provider-bills/{providerBill}/download', [\App\Http\Controllers\Api\ProviderBillController::class, 'downloadFile'])->middleware('auth:sanctum');
Route::apiResource('provider-bills', \App\Http\Controllers\Api\ProviderBillController::class)->middleware('auth:sanctum');

// Owner routes
Route::apiResource('owners', \App\Http\Controllers\Api\OwnerController::class)->middleware('auth:sanctum');

// Owner Equity routes
Route::get('/owner-equities/summary', [\App\Http\Controllers\Api\OwnerEquityController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/owners/{owner}/equity-summary', [\App\Http\Controllers\Api\OwnerEquityController::class, 'ownerSummary'])->middleware('auth:sanctum');
Route::apiResource('owner-equities', \App\Http\Controllers\Api\OwnerEquityController::class)->middleware('auth:sanctum');

// Profit routes
Route::get('/profit/percentages', [\App\Http\Controllers\Api\ProfitController::class, 'getPercentages'])->middleware('auth:sanctum');

// Transaction routes
Route::get('/transactions/summary', [\App\Http\Controllers\Api\TransactionController::class, 'summary'])->middleware('auth:sanctum');
Route::apiResource('transactions', \App\Http\Controllers\Api\TransactionController::class)->middleware('auth:sanctum');

// Bank Transfer routes
Route::get('/bank-transfers/accounts', [\App\Http\Controllers\Api\BankTransferController::class, 'getBankAccounts'])->middleware('auth:sanctum');
Route::get('/bank-transfers/history', [\App\Http\Controllers\Api\BankTransferController::class, 'history'])->middleware('auth:sanctum');
Route::get('/bank-transfers/summary', [\App\Http\Controllers\Api\BankTransferController::class, 'summary'])->middleware('auth:sanctum');
Route::post('/bank-transfers/transfer', [\App\Http\Controllers\Api\BankTransferController::class, 'transfer'])->middleware('auth:sanctum');
Route::get('/bank-transfers/{id}', [\App\Http\Controllers\Api\BankTransferController::class, 'show'])->middleware('auth:sanctum');
Route::delete('/bank-transfers/{id}/cancel', [\App\Http\Controllers\Api\BankTransferController::class, 'cancel'])->middleware('auth:sanctum'); 