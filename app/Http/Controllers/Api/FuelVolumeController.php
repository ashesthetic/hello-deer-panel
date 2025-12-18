<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelVolume;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Utils\TimezoneUtil;

class FuelVolumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $shift = $request->input('shift');
        
        // Build query based on user role
        $query = FuelVolume::with('user');
        
        // Apply user permissions
        $query->byUser($user);
        
        // Add date range filter if provided
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        // Add shift filter if provided
        if ($shift && in_array($shift, ['morning', 'evening'])) {
            $query->where('shift', $shift);
        }
        
        // Handle sorting
        $allowedSortFields = ['date', 'shift', 'regular_tc_volume', 'premium_tc_volume', 'diesel_tc_volume'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        
        // Always sort by date first, then by shift (evening first, morning second)
        if ($sortBy === 'date') {
            $query->orderBy('date', $sortDirection);
            $query->orderByRaw("CASE WHEN shift = 'evening' THEN 1 WHEN shift = 'morning' THEN 2 ELSE 3 END");
        } elseif ($sortBy === 'shift') {
            // When sorting by shift, still maintain date order within each shift
            $query->orderBy('shift', $sortDirection);
            $query->orderBy('date', 'desc');
        } else {
            // For other fields, sort by the field first, then by date and shift
            $query->orderBy($sortBy, $sortDirection);
            $query->orderBy('date', 'desc');
            $query->orderByRaw("CASE WHEN shift = 'evening' THEN 1 WHEN shift = 'morning' THEN 2 ELSE 3 END");
        }
        
        $fuelVolumes = $query->paginate($perPage);
        
        // Add calculated fields to each fuel volume entry
        $fuelVolumes->getCollection()->transform(function ($fuelVolume) {
            $fuelVolume->volume_end_of_day = $fuelVolume->volume_end_of_day;
            $fuelVolume->evening_shift = $fuelVolume->evening_shift;
            $fuelVolume->morning_shift = $fuelVolume->morning_shift;
            
            return $fuelVolume;
        });
        
        return response()->json($fuelVolumes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'shift' => ['required', Rule::in(['morning', 'evening'])],
            'regular_tc_volume' => 'nullable|numeric|min:0',
            'regular_product_height' => 'nullable|numeric|min:0',
            'premium_tc_volume' => 'nullable|numeric|min:0',
            'premium_product_height' => 'nullable|numeric|min:0',
            'diesel_tc_volume' => 'nullable|numeric|min:0',
            'diesel_product_height' => 'nullable|numeric|min:0',
            'added_regular' => 'nullable|numeric|min:0',
            'added_premium' => 'nullable|numeric|min:0',
            'added_diesel' => 'nullable|numeric|min:0',
        ]);

        // Check if entry already exists for this date and shift
        $existingEntry = FuelVolume::where('date', $request->date)
            ->where('shift', $request->shift)
            ->first();

        if ($existingEntry) {
            return response()->json([
                'message' => 'An entry already exists for this date and shift',
                'data' => $existingEntry
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $user->id; // Associate with current user
        
        $fuelVolume = FuelVolume::create($data);
        
        // Add calculated fields
        $fuelVolume->volume_end_of_day = $fuelVolume->volume_end_of_day;
        $fuelVolume->evening_shift = $fuelVolume->evening_shift;
        $fuelVolume->morning_shift = $fuelVolume->morning_shift;

        return response()->json([
            'message' => 'Fuel volume created successfully',
            'data' => $fuelVolume
        ]);
    }

    /**
     * Store a newly created fuel volume for Staff users
     */
    public function storeForStaff(Request $request)
    {
        $user = $request->user();
        
        // Staff users are specifically allowed to create fuel volumes through this endpoint
        if (!$user->isStaff()) {
            return response()->json(['message' => 'Unauthorized. This endpoint is only for staff users.'], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'shift' => ['required', Rule::in(['morning', 'evening'])],
            'regular_tc_volume' => 'nullable|numeric|min:0',
            'regular_product_height' => 'nullable|numeric|min:0',
            'premium_tc_volume' => 'nullable|numeric|min:0',
            'premium_product_height' => 'nullable|numeric|min:0',
            'diesel_tc_volume' => 'nullable|numeric|min:0',
            'diesel_product_height' => 'nullable|numeric|min:0',
            'added_regular' => 'nullable|numeric|min:0',
            'added_premium' => 'nullable|numeric|min:0',
            'added_diesel' => 'nullable|numeric|min:0',
        ]);

        // Check if entry already exists for this date and shift
        $existingEntry = FuelVolume::where('date', $request->date)
            ->where('shift', $request->shift)
            ->first();

        if ($existingEntry) {
            return response()->json([
                'message' => 'An entry already exists for this date and shift',
                'data' => $existingEntry
            ], 422);
        }

        $data = $request->all();
        $data['user_id'] = $user->id; // Associate with current user
        
        $fuelVolume = FuelVolume::create($data);
        
        // Add calculated fields
        $fuelVolume->volume_end_of_day = $fuelVolume->volume_end_of_day;
        $fuelVolume->evening_shift = $fuelVolume->evening_shift;
        $fuelVolume->morning_shift = $fuelVolume->morning_shift;

        return response()->json([
            'message' => 'Fuel volume created successfully',
            'data' => $fuelVolume
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, FuelVolume $fuelVolume)
    {
        $user = $request->user();
        
        // Check if user can view this specific fuel volume entry
        if ($user->isEditor() && $fuelVolume->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Add calculated fields
        $fuelVolume->volume_end_of_day = $fuelVolume->volume_end_of_day;
        $fuelVolume->evening_shift = $fuelVolume->evening_shift;
        $fuelVolume->morning_shift = $fuelVolume->morning_shift;

        return response()->json($fuelVolume);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FuelVolume $fuelVolume)
    {
        $user = $request->user();
        
        // Check if user can update this specific fuel volume entry
        if ($user->isEditor() && $fuelVolume->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if (!$user->canUpdate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'shift' => ['required', Rule::in(['morning', 'evening'])],
            'regular_tc_volume' => 'nullable|numeric|min:0',
            'regular_product_height' => 'nullable|numeric|min:0',
            'premium_tc_volume' => 'nullable|numeric|min:0',
            'premium_product_height' => 'nullable|numeric|min:0',
            'diesel_tc_volume' => 'nullable|numeric|min:0',
            'diesel_product_height' => 'nullable|numeric|min:0',
            'added_regular' => 'nullable|numeric|min:0',
            'added_premium' => 'nullable|numeric|min:0',
            'added_diesel' => 'nullable|numeric|min:0',
        ]);

        // Check if entry already exists for this date and shift (excluding current entry)
        $existingEntry = FuelVolume::where('date', $request->date)
            ->where('shift', $request->shift)
            ->where('id', '!=', $fuelVolume->id)
            ->first();

        if ($existingEntry) {
            return response()->json([
                'message' => 'An entry already exists for this date and shift',
                'data' => $existingEntry
            ], 422);
        }

        $fuelVolume->update($request->all());
        
        // Add calculated fields
        $fuelVolume->volume_end_of_day = $fuelVolume->volume_end_of_day;
        $fuelVolume->evening_shift = $fuelVolume->evening_shift;
        $fuelVolume->morning_shift = $fuelVolume->morning_shift;

        return response()->json([
            'message' => 'Fuel volume updated successfully',
            'data' => $fuelVolume
        ]);
    }

    /**
     * Get fuel volumes for a specific month
     */
    public function getByMonth(Request $request, $year = null, $month = null)
    {
        $user = $request->user();
        $year = $year ?: TimezoneUtil::now()->format('Y');
        $month = $month ?: TimezoneUtil::now()->format('n');
        
        // Build query based on user role
        $query = FuelVolume::with('user');
        
        // Apply user permissions
        $query->byUser($user);
        
        $fuelVolumes = $query->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->orderByRaw("CASE WHEN shift = 'evening' THEN 1 WHEN shift = 'morning' THEN 2 ELSE 3 END")
            ->get();
        
        // Add calculated fields to each fuel volume entry
        $fuelVolumes->transform(function ($fuelVolume) {
            $fuelVolume->volume_end_of_day = $fuelVolume->volume_end_of_day;
            $fuelVolume->evening_shift = $fuelVolume->evening_shift;
            $fuelVolume->morning_shift = $fuelVolume->morning_shift;
            
            return $fuelVolume;
        });
        
        return response()->json([
            'data' => $fuelVolumes,
            'year' => $year,
            'month' => $month
        ]);
    }

    /**
     * Get daily summary with both shifts and end of day calculations
     */
    public function getDailySummary(Request $request, $date = null)
    {
        $user = $request->user();
        $date = $date ?: TimezoneUtil::now()->format('Y-m-d');
        
        // Get both shifts for the date
        $morningShift = FuelVolume::with('user')
            ->byUser($user)
            ->where('date', $date)
            ->where('shift', 'morning')
            ->first();
            
        $eveningShift = FuelVolume::with('user')
            ->byUser($user)
            ->where('date', $date)
            ->where('shift', 'evening')
            ->first();
        
        // Calculate end of day volumes
        $endOfDayVolumes = null;
        if ($eveningShift) {
            $endOfDayVolumes = [
                'regular' => ($eveningShift->regular_tc_volume ?? 0) + ($eveningShift->added_regular ?? 0),
                'premium' => ($eveningShift->premium_tc_volume ?? 0) + ($eveningShift->added_premium ?? 0),
                'diesel' => ($eveningShift->diesel_tc_volume ?? 0) + ($eveningShift->added_diesel ?? 0),
            ];
        }
        
        return response()->json([
            'date' => $date,
            'morning_shift' => $morningShift,
            'evening_shift' => $eveningShift,
            'end_of_day_volumes' => $endOfDayVolumes
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, FuelVolume $fuelVolume)
    {
        $user = $request->user();
        
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fuelVolume->delete();

        return response()->json([
            'message' => 'Fuel volume deleted successfully'
        ]);
    }
}
