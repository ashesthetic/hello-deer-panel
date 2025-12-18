<?php

namespace App\Http\Controllers;

use App\Models\SmokesCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmokesCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = SmokesCategory::query();
        
        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $categories = $query->paginate($perPage);
        
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = SmokesCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Smokes category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = SmokesCategory::findOrFail($id);
        
        return response()->json([
            'data' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = SmokesCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->only(['name']));

        return response()->json([
            'message' => 'Smokes category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $category = SmokesCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Smokes category deleted successfully'
        ]);
    }

    /**
     * Get all categories including soft deleted ones.
     */
    public function withTrashed(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = SmokesCategory::withTrashed();
        
        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at', 'deleted_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $categories = $query->paginate($perPage);
        
        return response()->json($categories);
    }

    /**
     * Restore a soft deleted category.
     */
    public function restore(string $id)
    {
        $category = SmokesCategory::withTrashed()->findOrFail($id);
        
        if (!$category->trashed()) {
            return response()->json([
                'message' => 'Category is not deleted'
            ], 400);
        }
        
        $category->restore();
        
        return response()->json([
            'message' => 'Smokes category restored successfully',
            'data' => $category
        ]);
    }

    /**
     * Permanently delete a soft deleted category.
     */
    public function forceDelete(string $id)
    {
        $category = SmokesCategory::withTrashed()->findOrFail($id);
        $category->forceDelete();

        return response()->json([
            'message' => 'Smokes category permanently deleted'
        ]);
    }
}
