<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $search = $request->input('search');
        
        // Build query based on user role
        $query = Vendor::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('possible_products', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        // Handle sorting
        $allowedSortFields = ['name', 'payment_method', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'name';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $vendors = $query->paginate($perPage);
        
        return response()->json($vendors);
    }

    /**
     * Store a newly created vendor
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_phone' => 'nullable|string|max:255',
            'contact_person_title' => 'nullable|string|max:255',
            'possible_products' => 'required|string',
            'payment_method' => 'required|in:PAD,Credit Card,E-transfer,Direct Deposit',
            'etransfer_email' => 'nullable|email|required_if:payment_method,E-transfer',
            'bank_name' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'transit_number' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'institute_number' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'account_number' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'void_check' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'order_before_days' => 'required|array|min:1',
            'order_before_days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'possible_delivery_days' => 'required|array|min:1',
            'possible_delivery_days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_email' => $request->contact_person_email,
            'contact_person_phone' => $request->contact_person_phone,
            'contact_person_title' => $request->contact_person_title,
            'possible_products' => $request->possible_products,
            'payment_method' => $request->payment_method,
            'order_before_days' => $request->order_before_days,
            'possible_delivery_days' => $request->possible_delivery_days,
            'notes' => $request->notes,
            'user_id' => $user->id,
        ];

        // Handle payment method specific fields
        if ($request->payment_method === 'E-transfer') {
            $data['etransfer_email'] = $request->etransfer_email;
        } elseif ($request->payment_method === 'Direct Deposit') {
            $data['bank_name'] = $request->bank_name;
            $data['transit_number'] = $request->transit_number;
            $data['institute_number'] = $request->institute_number;
            $data['account_number'] = $request->account_number;
        }

        // Handle file upload
        if ($request->hasFile('void_check')) {
            $path = $request->file('void_check')->store('vendor-documents', 'public');
            $data['void_check_path'] = $path;
        }

        $vendor = Vendor::create($data);

        return response()->json([
            'message' => 'Vendor created successfully',
            'data' => $vendor->load('user')
        ], 201);
    }

    /**
     * Display the specified vendor
     */
    public function show(Request $request, Vendor $vendor)
    {
        $user = $request->user();
        
        // Check if user can view this vendor
        if ($user->isEditor() && $vendor->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($vendor->load('user'));
    }

    /**
     * Update the specified vendor
     */
    public function update(Request $request, Vendor $vendor)
    {
        $user = $request->user();
        
        if (!$vendor->canBeUpdatedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_phone' => 'nullable|string|max:255',
            'contact_person_title' => 'nullable|string|max:255',
            'possible_products' => 'required|string',
            'payment_method' => 'required|in:PAD,Credit Card,E-transfer,Direct Deposit',
            'etransfer_email' => 'nullable|email|required_if:payment_method,E-transfer',
            'bank_name' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'transit_number' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'institute_number' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'account_number' => 'nullable|string|required_if:payment_method,Direct Deposit',
            'void_check' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'order_before_days' => 'required|array|min:1',
            'order_before_days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'possible_delivery_days' => 'required|array|min:1',
            'possible_delivery_days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_email' => $request->contact_person_email,
            'contact_person_phone' => $request->contact_person_phone,
            'contact_person_title' => $request->contact_person_title,
            'possible_products' => $request->possible_products,
            'payment_method' => $request->payment_method,
            'order_before_days' => $request->order_before_days,
            'possible_delivery_days' => $request->possible_delivery_days,
            'notes' => $request->notes,
        ];

        // Handle payment method specific fields
        if ($request->payment_method === 'E-transfer') {
            $data['etransfer_email'] = $request->etransfer_email;
            // Clear direct deposit fields
            $data['bank_name'] = null;
            $data['transit_number'] = null;
            $data['institute_number'] = null;
            $data['account_number'] = null;
        } elseif ($request->payment_method === 'Direct Deposit') {
            $data['bank_name'] = $request->bank_name;
            $data['transit_number'] = $request->transit_number;
            $data['institute_number'] = $request->institute_number;
            $data['account_number'] = $request->account_number;
            // Clear e-transfer field
            $data['etransfer_email'] = null;
        } else {
            // Clear all payment method specific fields for PAD and Credit Card
            $data['etransfer_email'] = null;
            $data['bank_name'] = null;
            $data['transit_number'] = null;
            $data['institute_number'] = null;
            $data['account_number'] = null;
        }

        // Handle file upload
        if ($request->hasFile('void_check')) {
            // Delete old file if exists
            if ($vendor->void_check_path) {
                Storage::disk('public')->delete($vendor->void_check_path);
            }
            $path = $request->file('void_check')->store('vendor-documents', 'public');
            $data['void_check_path'] = $path;
        }

        $vendor->update($data);

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => $vendor->load('user')
        ]);
    }

    /**
     * Remove the specified vendor
     */
    public function destroy(Request $request, Vendor $vendor)
    {
        $user = $request->user();
        
        if (!$vendor->canBeDeletedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated file if exists
        if ($vendor->void_check_path) {
            Storage::disk('public')->delete($vendor->void_check_path);
        }

        $vendor->delete();

        return response()->json([
            'message' => 'Vendor deleted successfully'
        ]);
    }
}
