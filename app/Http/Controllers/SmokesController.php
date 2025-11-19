<?php

namespace App\Http\Controllers;

use App\Models\Smokes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmokesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = Smokes::query();
        
        $allowedSortFields = ['id', 'date', 'item', 'shift', 'start', 'end', 'added', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $smokes = $query->paginate($perPage);
        
        return response()->json($smokes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'item' => 'required|string|max:255',
            'shift' => 'required|in:Morning,Evening',
            'start' => 'required|numeric|min:0',
            'end' => 'required|numeric|min:0',
            'added' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $smoke = Smokes::create([
            'date' => $request->date,
            'item' => $request->item,
            'shift' => $request->shift,
            'start' => $request->start,
            'end' => $request->end,
            'added' => $request->added ?? 0,
        ]);

        return response()->json([
            'message' => 'Smoke entry created successfully',
            'data' => $smoke
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $smoke = Smokes::findOrFail($id);
        
        return response()->json([
            'data' => $smoke
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $smoke = Smokes::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'item' => 'sometimes|required|string|max:255',
            'shift' => 'sometimes|required|in:Morning,Evening',
            'start' => 'sometimes|required|numeric|min:0',
            'end' => 'sometimes|required|numeric|min:0',
            'added' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $smoke->update($request->only(['date', 'item', 'shift', 'start', 'end', 'added']));

        return response()->json([
            'message' => 'Smoke entry updated successfully',
            'data' => $smoke
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $smoke = Smokes::findOrFail($id);
        $smoke->delete();

        return response()->json([
            'message' => 'Smoke entry deleted successfully'
        ]);
    }

    /**
     * Get all smokes including soft deleted ones.
     */
    public function withTrashed(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = Smokes::withTrashed();
        
        $allowedSortFields = ['id', 'date', 'item', 'shift', 'start', 'end', 'added', 'created_at', 'updated_at', 'deleted_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $smokes = $query->paginate($perPage);
        
        return response()->json($smokes);
    }

    /**
     * Restore a soft deleted smoke entry.
     */
    public function restore(string $id)
    {
        $smoke = Smokes::withTrashed()->findOrFail($id);
        
        if (!$smoke->trashed()) {
            return response()->json([
                'message' => 'Smoke entry is not deleted'
            ], 400);
        }
        
        $smoke->restore();
        
        return response()->json([
            'message' => 'Smoke entry restored successfully',
            'data' => $smoke
        ]);
    }

    /**
     * Permanently delete a soft deleted smoke entry.
     */
    public function forceDelete(string $id)
    {
        $smoke = Smokes::withTrashed()->findOrFail($id);
        $smoke->forceDelete();

        return response()->json([
            'message' => 'Smoke entry permanently deleted'
        ]);
    }
}
