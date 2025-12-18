<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderBill;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProviderBillController extends Controller
{
    /**
     * Display a listing of provider bills
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'billing_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $search = $request->input('search');
        $status = $request->input('status');
        $providerId = $request->input('provider_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query based on user role
        $query = ProviderBill::with(['provider', 'user']);
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('provider', function($providerQuery) use ($search) {
                    $providerQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Add status filter
        if ($status) {
            if (strtolower($status) === 'pending') {
                $query->pending();
            } elseif (strtolower($status) === 'paid') {
                $query->paid();
            }
        }

        // Add provider filter
        if ($providerId) {
            $query->byProvider($providerId);
        }

        // Add date range filter
        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }
        
        // Handle sorting
        $allowedSortFields = ['billing_date', 'service_date_from', 'service_date_to', 'due_date', 'subtotal', 'gst', 'total', 'status', 'date_paid', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'billing_date';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $providerBills = $query->paginate($perPage);
        
        return response()->json($providerBills);
    }

    /**
     * Store a newly created provider bill
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'billing_date' => 'required|date',
            'service_date_from' => 'required|date',
            'service_date_to' => 'required|date|after_or_equal:service_date_from',
            'due_date' => 'required|date',
            'gst' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|in:Pending,Paid',
            'date_paid' => 'nullable|date|required_if:status,Paid',
        ]);

        $data = [
            'provider_id' => $request->provider_id,
            'billing_date' => $request->billing_date,
            'service_date_from' => $request->service_date_from,
            'service_date_to' => $request->service_date_to,
            'due_date' => $request->due_date,
            'gst' => $request->gst,
            'total' => $request->total,
            'notes' => $request->notes,
            'status' => $request->status,
            'user_id' => $user->id,
        ];

        // Handle date_paid field
        if ($request->status === 'Paid') {
            $data['date_paid'] = $request->date_paid;
        }

        // Handle file upload
        if ($request->hasFile('invoice_file')) {
            $path = $request->file('invoice_file')->store('provider-bills', 'public');
            $data['invoice_file_path'] = $path;
        }

        $providerBill = ProviderBill::create($data);

        return response()->json([
            'message' => 'Provider bill created successfully',
            'data' => $providerBill->load(['provider', 'user'])
        ], 201);
    }

    /**
     * Display the specified provider bill
     */
    public function show(Request $request, ProviderBill $providerBill)
    {
        $user = $request->user();
        
        // Check if user can view this provider bill
        if ($user->isEditor() && $providerBill->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($providerBill->load(['provider', 'user']));
    }

    /**
     * Update the specified provider bill
     */
    public function update(Request $request, ProviderBill $providerBill)
    {
        $user = $request->user();
        
        if (!$providerBill->canBeUpdatedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'billing_date' => 'required|date',
            'service_date_from' => 'required|date',
            'service_date_to' => 'required|date|after_or_equal:service_date_from',
            'due_date' => 'required|date',
            'gst' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|in:Pending,Paid',
            'date_paid' => 'nullable|date|required_if:status,Paid',
        ]);

        $data = [
            'provider_id' => $request->provider_id,
            'billing_date' => $request->billing_date,
            'service_date_from' => $request->service_date_from,
            'service_date_to' => $request->service_date_to,
            'due_date' => $request->due_date,
            'gst' => $request->gst,
            'total' => $request->total,
            'notes' => $request->notes,
            'status' => $request->status,
        ];

        // Handle date_paid field
        if ($request->status === 'Paid') {
            $data['date_paid'] = $request->date_paid;
        } else {
            $data['date_paid'] = null;
        }

        // Handle file upload
        if ($request->hasFile('invoice_file')) {
            // Delete old file if exists
            if ($providerBill->invoice_file_path) {
                Storage::disk('public')->delete($providerBill->invoice_file_path);
            }
            $path = $request->file('invoice_file')->store('provider-bills', 'public');
            $data['invoice_file_path'] = $path;
        }

        $providerBill->update($data);

        return response()->json([
            'message' => 'Provider bill updated successfully',
            'data' => $providerBill->load(['provider', 'user'])
        ]);
    }

    /**
     * Remove the specified provider bill
     */
    public function destroy(Request $request, ProviderBill $providerBill)
    {
        $user = $request->user();
        
        if (!$providerBill->canBeDeletedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated file if exists
        if ($providerBill->invoice_file_path) {
            Storage::disk('public')->delete($providerBill->invoice_file_path);
        }

        $providerBill->delete();

        return response()->json([
            'message' => 'Provider bill deleted successfully'
        ]);
    }

    /**
     * Get providers for dropdown
     */
    public function getProviders(Request $request)
    {
        $user = $request->user();
        
        $query = Provider::select('id', 'name', 'service');
        
        // Editors can only see their own providers
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $providers = $query->orderBy('name')->get();
        
        return response()->json($providers);
    }

    /**
     * Download invoice file
     */
    public function downloadFile(Request $request, ProviderBill $providerBill)
    {
        $user = $request->user();
        
        // Check if user can view this provider bill
        if ($user->isEditor() && $providerBill->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$providerBill->invoice_file_path) {
            return response()->json(['message' => 'No file found'], 404);
        }

        if (!Storage::disk('public')->exists($providerBill->invoice_file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($providerBill->invoice_file_path);
    }
}
