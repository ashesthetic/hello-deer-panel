<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Models\Vendor;
use App\Models\BankAccount;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorInvoiceController extends Controller
{
    /**
     * Display a listing of vendor invoices
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'invoice_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');
        $reference = $request->input('reference');
        $vendorId = $request->input('vendor_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentStartDate = $request->input('payment_start_date');
        $paymentEndDate = $request->input('payment_end_date');
        
        // Build query based on user role
        $query = VendorInvoice::with(['vendor', 'user']);
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Staff users can only see their own entries
        if ($user->isStaff()) {
            $query->where('user_id', $user->id);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Add status filter
        if ($status) {
            $query->byStatus($status);
        }

        // Add type filter
        if ($type) {
            $query->byType($type);
        }

        // Add reference filter
        if ($reference) {
            $query->byReference($reference);
        }

        // Add vendor filter
        if ($vendorId) {
            $query->byVendor($vendorId);
        }

        // Add date range filter
        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        // Add payment date range filter (for balance page)
        if ($paymentStartDate && $paymentEndDate) {
            $query->byPaymentDateRange($paymentStartDate, $paymentEndDate);
        }
        
        // Handle sorting
        $allowedSortFields = ['invoice_date', 'status', 'type', 'total', 'payment_date', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'invoice_date';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $invoices = $query->paginate($perPage);
        
        return response()->json($invoices);
    }

    /**
     * Store a newly created vendor invoice
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'status' => 'required|in:Paid,Unpaid',
            'type' => 'required|in:Income,Expense',
            'reference' => 'required|in:Vendor,Ash,Nafi',
            'payment_date' => 'nullable|date|required_if:status,Paid',
            'payment_method' => 'nullable|in:Card,Cash,Bank|required_if:status,Paid',
            'bank_account_id' => 'nullable|exists:bank_accounts,id|required_if:status,Paid',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'gst' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
        ]);

        $data = [
            'vendor_id' => $request->vendor_id,
            'invoice_number' => $request->invoice_number,
            'invoice_date' => $request->invoice_date,
            'status' => $request->status,
            'type' => $request->type,
            'reference' => $request->reference,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'bank_account_id' => $request->bank_account_id,
            'gst' => $request->gst,
            'total' => $request->total,
            'notes' => $request->notes,
            'description' => $request->description,
            'user_id' => $user->id,
        ];

        // Handle file upload
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $fileName = 'vendor_invoice_' . time() . '_' . $file->getClientOriginalName();
            
            $googleDriveService = new GoogleDriveService();
            
            // Check if Google Drive is authenticated
            if (!$googleDriveService->isAuthenticated()) {
                return response()->json([
                    'message' => 'Google Drive authentication required',
                    'error_code' => 'GOOGLE_AUTH_REQUIRED'
                ], 401);
            }
            
            $uploadResult = $googleDriveService->uploadFile($file, $fileName, $request->invoice_date);
            
            if ($uploadResult) {
                $data['google_drive_file_id'] = $uploadResult['file_id'];
                $data['google_drive_file_name'] = $uploadResult['name'];
                $data['google_drive_web_view_link'] = $uploadResult['web_view_link'];
                $data['invoice_file_path'] = null; // Clear local path as we're using Google Drive
            } else {
                return response()->json([
                    'message' => 'Failed to upload file to Google Drive'
                ], 500);
            }
        }

        $invoice = VendorInvoice::create($data);

        return response()->json([
            'message' => 'Vendor invoice created successfully',
            'data' => $invoice->load(['vendor', 'user'])
        ], 201);
    }

    /**
     * Store a newly created vendor invoice for Staff users (Unpaid only)
     */
    public function storeForStaff(Request $request)
    {
        $user = $request->user();
        
        // Staff users are specifically allowed to create invoices through this endpoint
        if (!$user->isStaff()) {
            return response()->json(['message' => 'Unauthorized. This endpoint is only for staff users.'], 403);
        }

        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'type' => 'required|in:Income,Expense',
            'reference' => 'required|in:Vendor,Ash,Nafi',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'gst' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
        ]);

        $data = [
            'vendor_id' => $request->vendor_id,
            'invoice_number' => $request->invoice_number,
            'invoice_date' => $request->invoice_date,
            'status' => 'Unpaid', // Force unpaid status for staff
            'type' => $request->type,
            'reference' => $request->reference,
            'payment_date' => null, // No payment date for unpaid invoices
            'payment_method' => null, // No payment method for unpaid invoices
            'bank_account_id' => null, // No bank account for unpaid invoices
            'gst' => $request->gst,
            'total' => $request->total,
            'notes' => $request->notes,
            'description' => $request->description,
            'user_id' => $user->id,
        ];

        // Handle file upload
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $fileName = 'vendor_invoice_' . time() . '_' . $file->getClientOriginalName();
            
            $googleDriveService = new GoogleDriveService();
            
            // Check if Google Drive is authenticated
            if (!$googleDriveService->isAuthenticated()) {
                return response()->json([
                    'message' => 'Google Drive authentication required',
                    'error_code' => 'GOOGLE_AUTH_REQUIRED'
                ], 401);
            }
            
            $uploadResult = $googleDriveService->uploadFile($file, $fileName, $request->invoice_date);
            
            if ($uploadResult) {
                $data['google_drive_file_id'] = $uploadResult['file_id'];
                $data['google_drive_file_name'] = $uploadResult['name'];
                $data['google_drive_web_view_link'] = $uploadResult['web_view_link'];
                $data['invoice_file_path'] = null; // Clear local path as we're using Google Drive
            } else {
                return response()->json([
                    'message' => 'Failed to upload file to Google Drive'
                ], 500);
            }
        }

        $invoice = VendorInvoice::create($data);

        return response()->json([
            'message' => 'Vendor invoice created successfully (Unpaid)',
            'data' => $invoice->load(['vendor', 'user'])
        ], 201);
    }

    /**
     * Display the specified vendor invoice
     */
    public function show(Request $request, VendorInvoice $vendorInvoice)
    {
        $user = $request->user();
        
        // Check if user can view this invoice
        if ($user->isEditor() && $vendorInvoice->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($vendorInvoice->load(['vendor', 'user']));
    }

    /**
     * Update the specified vendor invoice
     */
    public function update(Request $request, VendorInvoice $vendorInvoice)
    {
        $user = $request->user();
        
        if (!$vendorInvoice->canBeUpdatedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'required|date',
            'status' => 'required|in:Paid,Unpaid',
            'type' => 'required|in:Income,Expense',
            'reference' => 'required|in:Vendor,Ash,Nafi',
            'payment_date' => 'nullable|date|required_if:status,Paid',
            'payment_method' => 'nullable|in:Card,Cash,Bank|required_if:status,Paid',
            'bank_account_id' => 'nullable|exists:bank_accounts,id|required_if:status,Paid',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'gst' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
        ]);

        $data = [
            'vendor_id' => $request->vendor_id,
            'invoice_number' => $request->invoice_number,
            'invoice_date' => $request->invoice_date,
            'status' => $request->status,
            'type' => $request->type,
            'reference' => $request->reference,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'bank_account_id' => $request->bank_account_id,
            'gst' => $request->gst,
            'total' => $request->total,
            'notes' => $request->notes,
            'description' => $request->description,
        ];

        // Handle file upload
        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $fileName = 'vendor_invoice_' . time() . '_' . $file->getClientOriginalName();
            
            $googleDriveService = new GoogleDriveService();
            
            // Check if Google Drive is authenticated
            if (!$googleDriveService->isAuthenticated()) {
                return response()->json([
                    'message' => 'Google Drive authentication required',
                    'error_code' => 'GOOGLE_AUTH_REQUIRED'
                ], 401);
            }
            
            // Delete old file from Google Drive if exists
            if ($vendorInvoice->google_drive_file_id) {
                $googleDriveService->deleteFile($vendorInvoice->google_drive_file_id);
            }
            
            // Delete old local file if exists
            if ($vendorInvoice->invoice_file_path) {
                Storage::disk('public')->delete($vendorInvoice->invoice_file_path);
            }
            
            $uploadResult = $googleDriveService->uploadFile($file, $fileName, $request->invoice_date);
            
            if ($uploadResult) {
                $data['google_drive_file_id'] = $uploadResult['file_id'];
                $data['google_drive_file_name'] = $uploadResult['name'];
                $data['google_drive_web_view_link'] = $uploadResult['web_view_link'];
                $data['invoice_file_path'] = null; // Clear local path as we're using Google Drive
            } else {
                return response()->json([
                    'message' => 'Failed to upload file to Google Drive'
                ], 500);
            }
        }

        $vendorInvoice->update($data);

        return response()->json([
            'message' => 'Vendor invoice updated successfully',
            'data' => $vendorInvoice->load(['vendor', 'user'])
        ]);
    }

    /**
     * Remove the specified vendor invoice
     */
    public function destroy(Request $request, VendorInvoice $vendorInvoice)
    {
        $user = $request->user();
        
        if (!$vendorInvoice->canBeDeletedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated file if exists
        if ($vendorInvoice->google_drive_file_id) {
            $googleDriveService = new GoogleDriveService();
            $googleDriveService->deleteFile($vendorInvoice->google_drive_file_id);
        }
        
        // Delete local file if exists (for backward compatibility)
        if ($vendorInvoice->invoice_file_path) {
            Storage::disk('public')->delete($vendorInvoice->invoice_file_path);
        }

        $vendorInvoice->delete();

        return response()->json([
            'message' => 'Vendor invoice deleted successfully'
        ]);
    }

    /**
     * Get vendors for dropdown
     */
    public function getVendors(Request $request)
    {
        $user = $request->user();
        
        $query = Vendor::select('id', 'name');
        
        // Editors can only see their own vendors
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $vendors = $query->orderBy('name')->get();
        
        return response()->json($vendors);
    }

    /**
     * Get bank accounts for dropdown
     */
    public function getBankAccounts(Request $request)
    {
        $user = $request->user();
        
        $query = BankAccount::select('id', 'bank_name', 'account_name', 'account_number')
            ->where('is_active', true);
        
        // Apply ACL based on user role
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $bankAccounts = $query->orderBy('bank_name')->get();
        
        // Add formatted display name
        $bankAccounts = $bankAccounts->map(function ($account) {
            $account->display_name = "{$account->bank_name} - {$account->account_name} (...{$account->masked_account_number})";
            return $account;
        });
        
        return response()->json($bankAccounts);
    }

    /**
     * Download invoice file
     */
    public function downloadFile(Request $request, VendorInvoice $vendorInvoice)
    {
        $user = $request->user();
        
        // Check if user can view this invoice
        if ($user->isEditor() && $vendorInvoice->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check Google Drive file first
        if ($vendorInvoice->google_drive_file_id) {
            $googleDriveService = new GoogleDriveService();
            $fileContent = $googleDriveService->downloadFile($vendorInvoice->google_drive_file_id);
            
            if ($fileContent) {
                $fileName = $vendorInvoice->google_drive_file_name ?: 'invoice_file';
                
                return response($fileContent)
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            } else {
                return response()->json(['message' => 'Failed to download file from Google Drive'], 500);
            }
        }

        // Fallback to local file (for backward compatibility)
        if (!$vendorInvoice->invoice_file_path) {
            return response()->json(['message' => 'No file found'], 404);
        }

        if (!Storage::disk('public')->exists($vendorInvoice->invoice_file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($vendorInvoice->invoice_file_path);
    }

    /**
     * Get Google Drive view link for invoice file
     */
    public function getFileViewLink(Request $request, VendorInvoice $vendorInvoice)
    {
        $user = $request->user();
        
        // Check if user can view this invoice
        if ($user->isEditor() && $vendorInvoice->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$vendorInvoice->google_drive_web_view_link) {
            return response()->json(['message' => 'No Google Drive file found'], 404);
        }

        return response()->json([
            'view_link' => $vendorInvoice->google_drive_web_view_link,
            'file_name' => $vendorInvoice->google_drive_file_name
        ]);
    }
}
