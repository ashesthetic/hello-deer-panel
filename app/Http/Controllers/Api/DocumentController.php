<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Build query
        $query = Document::with(['employee']);
        
        // Handle sorting
        $allowedSortFields = ['id', 'name', 'to', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $documents = $query->paginate($perPage);
        
        return response()->json($documents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'to' => 'required|exists:employees,id',
            'document' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle file upload
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('documents', 'public');
        }

        $document = Document::create([
            'name' => $request->name,
            'to' => $request->to,
            'document' => $documentPath,
        ]);

        $document->load(['employee']);

        return response()->json([
            'message' => 'Document created successfully',
            'data' => $document
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $document = Document::with(['employee'])->findOrFail($id);
        
        return response()->json([
            'data' => $document
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $document = Document::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'to' => 'sometimes|required|exists:employees,id',
            'document' => 'sometimes|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle file upload if new file is provided
        if ($request->hasFile('document')) {
            // Delete old file
            if ($document->document && Storage::disk('public')->exists($document->document)) {
                Storage::disk('public')->delete($document->document);
            }
            $document->document = $request->file('document')->store('documents', 'public');
        }

        // Update other fields
        if ($request->has('name')) {
            $document->name = $request->name;
        }
        if ($request->has('to')) {
            $document->to = $request->to;
        }

        $document->save();
        $document->load(['employee']);

        return response()->json([
            'message' => 'Document updated successfully',
            'data' => $document
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $document = Document::findOrFail($id);
        
        // Delete file from storage
        if ($document->document && Storage::disk('public')->exists($document->document)) {
            Storage::disk('public')->delete($document->document);
        }
        
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ]);
    }

    /**
     * Display a listing of documents for the authenticated staff user.
     */
    public function indexForStaff(Request $request)
    {
        $user = $request->user();
        
        // Get the employee record by email (same approach as pay stubs)
        $employee = Employee::where('email', $user->email)->first();
        
        if (!$employee) {
            // Return empty paginated response format
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $request->input('per_page', 10),
                'total' => 0,
                'from' => null,
                'to' => null
            ]);
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Build query - only documents for this employee
        $query = Document::with(['employee'])
            ->where('to', $employee->id);
        
        // Handle sorting
        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $documents = $query->paginate($perPage);
        
        return response()->json($documents);
    }

    /**
     * Display the specified document for staff user.
     */
    public function showForStaff(Request $request, string $id)
    {
        $user = $request->user();
        
        // Get the employee record by email (same approach as pay stubs)
        $employee = Employee::where('email', $user->email)->first();
        
        if (!$employee) {
            return response()->json([
                'message' => 'No employee record found'
            ], 404);
        }

        // Find document and ensure it belongs to this employee
        $document = Document::with(['employee'])
            ->where('id', $id)
            ->where('to', $employee->id)
            ->first();
        
        if (!$document) {
            return response()->json([
                'message' => 'Document not found or you do not have access to it'
            ], 404);
        }
        
        return response()->json([
            'data' => $document
        ]);
    }
}
