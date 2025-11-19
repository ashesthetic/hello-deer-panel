<?php

namespace App\Http\Controllers;

use App\Models\Lottery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LotteryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = Lottery::query();
        
        $allowedSortFields = ['id', 'date', 'item', 'shift', 'start', 'end', 'added', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $lottery = $query->paginate($perPage);
        
        return response()->json($lottery);
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

        $lottery = Lottery::create([
            'date' => $request->date,
            'item' => $request->item,
            'shift' => $request->shift,
            'start' => $request->start,
            'end' => $request->end,
            'added' => $request->added ?? 0,
        ]);

        return response()->json([
            'message' => 'Lottery entry created successfully',
            'data' => $lottery
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $lottery = Lottery::findOrFail($id);
        
        return response()->json([
            'data' => $lottery
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $lottery = Lottery::findOrFail($id);

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

        $lottery->update([
            'date' => $request->date,
            'item' => $request->item,
            'shift' => $request->shift,
            'start' => $request->start,
            'end' => $request->end,
            'added' => $request->added ?? 0,
        ]);

        return response()->json([
            'message' => 'Lottery entry updated successfully',
            'data' => $lottery
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $lottery = Lottery::findOrFail($id);
        $lottery->delete();

        return response()->json([
            'message' => 'Lottery entry deleted successfully'
        ]);
    }

    /**
     * Get all trashed records.
     */
    public function trashed()
    {
        $lottery = Lottery::onlyTrashed()->paginate(10);
        
        return response()->json($lottery);
    }

    /**
     * Restore a soft deleted record.
     */
    public function restore(string $id)
    {
        $lottery = Lottery::withTrashed()->findOrFail($id);
        $lottery->restore();

        return response()->json([
            'message' => 'Lottery entry restored successfully',
            'data' => $lottery
        ]);
    }

    /**
     * Permanently delete a record.
     */
    public function forceDelete(string $id)
    {
        $lottery = Lottery::withTrashed()->findOrFail($id);
        $lottery->forceDelete();

        return response()->json([
            'message' => 'Lottery entry permanently deleted'
        ]);
    }
}
