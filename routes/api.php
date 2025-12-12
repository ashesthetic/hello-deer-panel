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

// Dashboard routes
Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'getStats'])->middleware('auth:sanctum');

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Google OAuth2 routes
Route::prefix('google')->group(function () {
    Route::get('/auth-url', [\App\Http\Controllers\GoogleAuthController::class, 'getAuthUrl'])->middleware('auth:sanctum');
    Route::get('/callback', [\App\Http\Controllers\GoogleAuthController::class, 'handleCallback']);
    Route::get('/auth-status', [\App\Http\Controllers\GoogleAuthController::class, 'getAuthStatus'])->middleware('auth:sanctum');
    Route::get('/test-connection', [\App\Http\Controllers\GoogleAuthController::class, 'testConnection'])->middleware('auth:sanctum');
    Route::post('/revoke', [\App\Http\Controllers\GoogleAuthController::class, 'revokeAccess'])->middleware('auth:sanctum');
});

// Daily Sales routes
Route::get('daily-sales/month/{year?}/{month?}', [\App\Http\Controllers\Api\DailySaleController::class, 'getByMonth'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('daily-sales/settlement-report', [\App\Http\Controllers\Api\DailySaleController::class, 'generateSettlementReport'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('settlement-dates', [\App\Http\Controllers\Api\DailySaleController::class, 'getSettlementDates'])->middleware(['auth:sanctum', 'not.staff']);
Route::put('settlement-dates', [\App\Http\Controllers\Api\DailySaleController::class, 'updateSettlementDates'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('daily-sales', \App\Http\Controllers\Api\DailySaleController::class)->middleware(['auth:sanctum', 'not.staff']);

// Daily Fuels routes
Route::get('daily-fuels/month/{year?}/{month?}', [\App\Http\Controllers\Api\DailyFuelController::class, 'getByMonth'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('daily-fuels', \App\Http\Controllers\Api\DailyFuelController::class)->middleware(['auth:sanctum', 'not.staff']);

// Daily ATM routes (Admin only)
Route::post('daily-atm/{daily_atm}/resolve', [\App\Http\Controllers\Api\DailyAtmController::class, 'resolve'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::apiResource('daily-atm', \App\Http\Controllers\Api\DailyAtmController::class)->middleware(['auth:sanctum', 'can.manage.users']);

// Fuel Volume routes
Route::get('fuel-volumes/month/{year?}/{month?}', [\App\Http\Controllers\Api\FuelVolumeController::class, 'getByMonth'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('fuel-volumes/daily-summary/{date?}', [\App\Http\Controllers\Api\FuelVolumeController::class, 'getDailySummary'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('fuel-volumes', \App\Http\Controllers\Api\FuelVolumeController::class)->middleware(['auth:sanctum', 'not.staff']);

// Vendor routes
Route::apiResource('vendors', \App\Http\Controllers\Api\VendorController::class)->middleware(['auth:sanctum', 'not.staff']);

// Bank Account routes
Route::get('/bank-accounts/summary', [\App\Http\Controllers\Api\BankAccountController::class, 'summary'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('bank-accounts', \App\Http\Controllers\Api\BankAccountController::class)->middleware(['auth:sanctum', 'not.staff']);

// Safedrop Resolution routes
Route::get('/safedrop-resolutions/history', [\App\Http\Controllers\Api\SafedropResolutionController::class, 'history'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('safedrop-resolutions', \App\Http\Controllers\Api\SafedropResolutionController::class)->only(['index', 'store', 'destroy'])->middleware(['auth:sanctum', 'not.staff']);

// User management routes (Admin only)
Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->middleware(['auth:sanctum', 'can.manage.users']);
// User profile routes
Route::get('/user/profile', [\App\Http\Controllers\Api\UserController::class, 'profile'])->middleware('auth:sanctum');
Route::put('/user/profile', [\App\Http\Controllers\Api\UserController::class, 'updateProfile'])->middleware('auth:sanctum');

// Employee routes
Route::get('/employees/stats', [\App\Http\Controllers\Api\EmployeeController::class, 'stats'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/employees/with-hours', [\App\Http\Controllers\Api\EmployeeController::class, 'employeesWithHours'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/employees/earnings', [\App\Http\Controllers\Api\EmployeeController::class, 'earnings'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/employees/pay-days', [\App\Http\Controllers\Api\EmployeeController::class, 'getPayDays'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/employees/work-hour-report', [\App\Http\Controllers\Api\EmployeeController::class, 'generateWorkHourReport'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/employees/pay-stubs', [\App\Http\Controllers\Api\EmployeeController::class, 'generatePayStubs'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/employees/pay-stubs-editable', [\App\Http\Controllers\Api\EmployeeController::class, 'generatePayStubsEditable'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/employees/{employee}/resolve-hours', [\App\Http\Controllers\Api\EmployeeController::class, 'resolveHours'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('employees', \App\Http\Controllers\Api\EmployeeController::class)->middleware(['auth:sanctum', 'not.staff']);

// Work Hours routes
Route::post('/work-hours/bulk', [\App\Http\Controllers\Api\WorkHourController::class, 'bulkStore'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('work-hours', \App\Http\Controllers\Api\WorkHourController::class)->middleware(['auth:sanctum', 'not.staff']);
Route::get('/work-hours/recent', [\App\Http\Controllers\Api\WorkHourController::class, 'recent'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/work-hours/summary', [\App\Http\Controllers\Api\WorkHourController::class, 'summary'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/employees/{employee}/work-hours', [\App\Http\Controllers\Api\WorkHourController::class, 'employeeHours'])->middleware(['auth:sanctum', 'not.staff']);

// Work Schedule routes
Route::apiResource('work-schedules', \App\Http\Controllers\Api\WorkScheduleController::class)->middleware(['auth:sanctum', 'not.staff']);
Route::get('/work-schedules/current-week', [\App\Http\Controllers\Api\WorkScheduleController::class, 'currentWeekSchedules'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/work-schedules/stats', [\App\Http\Controllers\Api\WorkScheduleController::class, 'stats'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/work-schedules/employees-without-current-week', [\App\Http\Controllers\Api\WorkScheduleController::class, 'employeesWithoutCurrentWeekSchedule'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/work-schedules/week-options', [\App\Http\Controllers\Api\WorkScheduleController::class, 'getWeekOptions'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/employees/{employee}/work-schedules', [\App\Http\Controllers\Api\WorkScheduleController::class, 'employeeSchedules'])->middleware(['auth:sanctum', 'not.staff']);

// Schedule routes (new simplified system)
Route::get('/schedules/current-week', [\App\Http\Controllers\Api\ScheduleController::class, 'currentWeek'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/schedules/stats', [\App\Http\Controllers\Api\ScheduleController::class, 'stats'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/schedules/email', [\App\Http\Controllers\Api\ScheduleController::class, 'emailSchedule'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('schedules', \App\Http\Controllers\Api\ScheduleController::class)->middleware(['auth:sanctum', 'not.staff']);

// Staff-specific Schedule routes (View only)
Route::get('/staff/schedules', [\App\Http\Controllers\Api\ScheduleController::class, 'index'])->middleware(['auth:sanctum']);

// Vendor Invoice routes (Non-staff users only)
Route::get('/vendor-invoices/vendors', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'getVendors'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/vendor-invoices/bank-accounts', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'getBankAccounts'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/vendor-invoices/{vendorInvoice}/download', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'downloadFile'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/vendor-invoices/{vendorInvoice}/view-link', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'getFileViewLink'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('vendor-invoices', \App\Http\Controllers\Api\VendorInvoiceController::class)->middleware(['auth:sanctum', 'not.staff']);

// Staff-specific Vendor Invoice routes (Limited access)
Route::get('/staff/vendor-invoices/vendors', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'getVendors'])->middleware(['auth:sanctum']);
Route::get('/staff/vendor-invoices', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'index'])->middleware(['auth:sanctum']);
Route::get('/staff/vendor-invoices/{vendorInvoice}', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'show'])->middleware(['auth:sanctum']);
Route::post('/staff/vendor-invoices', [\App\Http\Controllers\Api\VendorInvoiceController::class, 'storeForStaff'])->middleware(['auth:sanctum']);

// Staff-specific Fuel Volume routes (Limited access - Add only)
Route::get('/staff/fuel-volumes', [\App\Http\Controllers\Api\FuelVolumeController::class, 'index'])->middleware(['auth:sanctum']);
Route::get('/staff/fuel-volumes/{fuelVolume}', [\App\Http\Controllers\Api\FuelVolumeController::class, 'show'])->middleware(['auth:sanctum']);
Route::post('/staff/fuel-volumes', [\App\Http\Controllers\Api\FuelVolumeController::class, 'storeForStaff'])->middleware(['auth:sanctum']);

// Provider routes
Route::apiResource('providers', \App\Http\Controllers\Api\ProviderController::class)->middleware(['auth:sanctum', 'not.staff']);

// Provider Bill routes
Route::get('/provider-bills/providers', [\App\Http\Controllers\Api\ProviderBillController::class, 'getProviders'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/provider-bills/{providerBill}/download', [\App\Http\Controllers\Api\ProviderBillController::class, 'downloadFile'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('provider-bills', \App\Http\Controllers\Api\ProviderBillController::class)->middleware(['auth:sanctum', 'not.staff']);

// Owner routes
Route::apiResource('owners', \App\Http\Controllers\Api\OwnerController::class)->middleware(['auth:sanctum', 'not.staff']);

// Owner Equity routes
Route::get('/owner-equities/summary', [\App\Http\Controllers\Api\OwnerEquityController::class, 'summary'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/owners/{owner}/equity-summary', [\App\Http\Controllers\Api\OwnerEquityController::class, 'ownerSummary'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('owner-equities', \App\Http\Controllers\Api\OwnerEquityController::class)->middleware(['auth:sanctum', 'not.staff']);

// Profit routes
// File Import routes
Route::post('/file-imports/upload', [\App\Http\Controllers\Api\FileImportController::class, 'uploadFiles'])->middleware('auth:sanctum');
Route::get('/file-imports/stats', [\App\Http\Controllers\Api\FileImportController::class, 'stats'])->middleware('auth:sanctum');
Route::apiResource('file-imports', \App\Http\Controllers\Api\FileImportController::class)->middleware('auth:sanctum');

// SFT Upload routes (All authenticated users including Staff)
Route::post('/sft-uploads/upload', [\App\Http\Controllers\Api\SftUploadController::class, 'upload'])->middleware('auth:sanctum');
Route::get('/sft-uploads/stats', [\App\Http\Controllers\Api\SftUploadController::class, 'stats'])->middleware('auth:sanctum');
Route::get('/sft-uploads/{sftUpload}/extract-files', [\App\Http\Controllers\Api\SftUploadController::class, 'extractAndListFiles'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/sft-uploads/{sftUpload}/import-files', [\App\Http\Controllers\Api\SftUploadController::class, 'importSftFiles'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('sft-uploads', \App\Http\Controllers\Api\SftUploadController::class)->middleware('auth:sanctum');

// SFT Processing routes (Admin only)
Route::post('/sft-processor/process-sales-data', [\App\Http\Controllers\Api\SftProcessorController::class, 'processSalesData'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/sft-processor/available-dates', [\App\Http\Controllers\Api\SftProcessorController::class, 'getAvailableImportDates'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/sft-processor/files-for-date', [\App\Http\Controllers\Api\SftProcessorController::class, 'getSftFilesForDate'])->middleware(['auth:sanctum', 'not.staff']); 

Route::get('/profit/percentages', [\App\Http\Controllers\Api\ProfitController::class, 'getPercentages'])->middleware(['auth:sanctum', 'not.staff']);

// Transaction routes
Route::get('/transactions/summary', [\App\Http\Controllers\Api\TransactionController::class, 'summary'])->middleware(['auth:sanctum', 'not.staff']);
Route::apiResource('transactions', \App\Http\Controllers\Api\TransactionController::class)->middleware(['auth:sanctum', 'not.staff']);

// Bank Transfer routes
Route::get('/bank-transfers/accounts', [\App\Http\Controllers\Api\BankTransferController::class, 'getBankAccounts'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/bank-transfers/history', [\App\Http\Controllers\Api\BankTransferController::class, 'history'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/bank-transfers/summary', [\App\Http\Controllers\Api\BankTransferController::class, 'summary'])->middleware(['auth:sanctum', 'not.staff']);
Route::post('/bank-transfers/transfer', [\App\Http\Controllers\Api\BankTransferController::class, 'transfer'])->middleware(['auth:sanctum', 'not.staff']);
Route::get('/bank-transfers/{id}', [\App\Http\Controllers\Api\BankTransferController::class, 'show'])->middleware(['auth:sanctum', 'not.staff']);
Route::delete('/bank-transfers/{id}/cancel', [\App\Http\Controllers\Api\BankTransferController::class, 'cancel'])->middleware(['auth:sanctum', 'not.staff']); 

// Loan routes (Admin only)
Route::get('/loans/with-trashed', [\App\Http\Controllers\LoanController::class, 'withTrashed'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::get('/loans/{id}/payment-history', [\App\Http\Controllers\LoanController::class, 'paymentHistory'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::post('/loans/{id}/restore', [\App\Http\Controllers\LoanController::class, 'restore'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::post('/loans/{id}/payment', [\App\Http\Controllers\LoanController::class, 'processPayment'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/loans/{id}/force-delete', [\App\Http\Controllers\LoanController::class, 'forceDelete'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::apiResource('loans', \App\Http\Controllers\LoanController::class)->middleware(['auth:sanctum', 'can.manage.users']);

// Staff Pay Stubs routes (Staff can view their own pay stubs)
Route::get('/staff/pay-stubs', [\App\Http\Controllers\PayrollController::class, 'myPayStubs'])->middleware(['auth:sanctum']);
Route::get('/staff/pay-stubs/{id}', [\App\Http\Controllers\PayrollController::class, 'myPayStub'])->middleware(['auth:sanctum']);

// Payroll routes (Admin only - Staff has no access)
Route::post('/payrolls/bulk', [\App\Http\Controllers\PayrollController::class, 'bulkStore'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::get('/payrolls/with-trashed', [\App\Http\Controllers\PayrollController::class, 'withTrashed'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::get('/payrolls/{employeeId}/summary', [\App\Http\Controllers\PayrollController::class, 'summaryByEmployee'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::post('/payrolls/{id}/restore', [\App\Http\Controllers\PayrollController::class, 'restore'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::delete('/payrolls/{id}/force-delete', [\App\Http\Controllers\PayrollController::class, 'forceDelete'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::post('/payrolls/{id}/email', [\App\Http\Controllers\PayrollController::class, 'emailPayStub'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::apiResource('payrolls', \App\Http\Controllers\PayrollController::class)->middleware(['auth:sanctum', 'check.not.staff']);

// Payroll Report routes (Admin only - Staff has no access)
Route::get('/payroll-reports', [\App\Http\Controllers\PayrollReportController::class, 'index'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::post('/payroll-reports/upload', [\App\Http\Controllers\PayrollReportController::class, 'upload'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::post('/payroll-reports/match-employees', [\App\Http\Controllers\PayrollReportController::class, 'matchEmployees'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::get('/payroll-reports/{id}', [\App\Http\Controllers\PayrollReportController::class, 'show'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::post('/payroll-reports/{id}/process', [\App\Http\Controllers\PayrollReportController::class, 'process'])->middleware(['auth:sanctum', 'check.not.staff']);
Route::delete('/payroll-reports/{id}', [\App\Http\Controllers\PayrollReportController::class, 'destroy'])->middleware(['auth:sanctum', 'check.not.staff']);

// Smokes Category routes (Admin has full access, Staff can only read)
Route::get('/smokes-categories/with-trashed', [\App\Http\Controllers\SmokesCategoryController::class, 'withTrashed'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::post('/smokes-categories/{id}/restore', [\App\Http\Controllers\SmokesCategoryController::class, 'restore'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/smokes-categories/{id}/force-delete', [\App\Http\Controllers\SmokesCategoryController::class, 'forceDelete'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/smokes-categories/{id}', [\App\Http\Controllers\SmokesCategoryController::class, 'destroy'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::get('/smokes-categories', [\App\Http\Controllers\SmokesCategoryController::class, 'index'])->middleware(['auth:sanctum']); // Staff can read
Route::get('/smokes-categories/{id}', [\App\Http\Controllers\SmokesCategoryController::class, 'show'])->middleware(['auth:sanctum']); // Staff can read
Route::post('/smokes-categories', [\App\Http\Controllers\SmokesCategoryController::class, 'store'])->middleware(['auth:sanctum', 'can.manage.users']); // Only admin can create
Route::put('/smokes-categories/{id}', [\App\Http\Controllers\SmokesCategoryController::class, 'update'])->middleware(['auth:sanctum', 'can.manage.users']); // Only admin can update
Route::patch('/smokes-categories/{id}', [\App\Http\Controllers\SmokesCategoryController::class, 'update'])->middleware(['auth:sanctum', 'can.manage.users']); // Only admin can update

// Smokes routes (Admin has full access, Staff can add/edit only)
Route::get('/smokes/with-trashed', [\App\Http\Controllers\SmokesController::class, 'withTrashed'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::post('/smokes/{id}/restore', [\App\Http\Controllers\SmokesController::class, 'restore'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/smokes/{id}/force-delete', [\App\Http\Controllers\SmokesController::class, 'forceDelete'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/smokes/{id}', [\App\Http\Controllers\SmokesController::class, 'destroy'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::apiResource('smokes', \App\Http\Controllers\SmokesController::class)->except(['destroy'])->middleware(['auth:sanctum']);

// Lottery routes (Admin has full access, Staff can add/edit only)
Route::get('/lottery/with-trashed', [\App\Http\Controllers\LotteryController::class, 'withTrashed'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::post('/lottery/{id}/restore', [\App\Http\Controllers\LotteryController::class, 'restore'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/lottery/{id}/force-delete', [\App\Http\Controllers\LotteryController::class, 'forceDelete'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::delete('/lottery/{id}', [\App\Http\Controllers\LotteryController::class, 'destroy'])->middleware(['auth:sanctum', 'can.manage.users']);
Route::apiResource('lottery', \App\Http\Controllers\LotteryController::class)->except(['destroy'])->middleware(['auth:sanctum']);

