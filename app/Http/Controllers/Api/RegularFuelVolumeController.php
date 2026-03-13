<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegularFuelVolume;
use Illuminate\Http\Request;
use App\Utils\TimezoneUtil;

class RegularFuelVolumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage       = $request->input('per_page', 10);
        $sortBy        = $request->input('sort_by', 'datetime');
        $sortDirection = $request->input('sort_direction', 'desc');
        $startDatetime = $request->input('start_datetime');
        $endDatetime   = $request->input('end_datetime');
        $startDate     = $request->input('start_date');
        $endDate       = $request->input('end_date');

        $query = RegularFuelVolume::query();

        // Apply datetime range filter if provided
        if ($startDatetime) {
            $query->where('datetime', '>=', $startDatetime);
        }
        if ($endDatetime) {
            $query->where('datetime', '<=', $endDatetime);
        }

        // Apply date range filter if provided
        if ($startDate) {
            $query->whereDate('datetime', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('datetime', '<=', $endDate);
        }

        // Handle sorting
        $allowedSortFields = [
            'datetime',
            'regular_volume', 'regular_height', 'regular_ullage',
            'premium_volume', 'premium_height', 'premium_ullage',
            'diesel_volume', 'diesel_height', 'diesel_ullage',
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'datetime';
        }

        $query->orderBy($sortBy, $sortDirection);

        $records = $query->paginate($perPage);

        return response()->json($records);
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
            'datetime'        => 'required|date',
            'regular_volume'  => 'nullable|numeric',
            'regular_height'  => 'nullable|numeric',
            'regular_ullage'  => 'nullable|numeric',
            'regular_water'   => 'nullable|numeric',
            'regular_temp'    => 'nullable|numeric',
            'regular_fill'    => 'nullable|numeric',
            'regular_status'  => 'nullable|string|max:255',
            'premium_volume'  => 'nullable|numeric',
            'premium_height'  => 'nullable|numeric',
            'premium_ullage'  => 'nullable|numeric',
            'premium_water'   => 'nullable|numeric',
            'premium_temp'    => 'nullable|numeric',
            'premium_fill'    => 'nullable|numeric',
            'premium_status'  => 'nullable|string|max:255',
            'diesel_volume'   => 'nullable|numeric',
            'diesel_height'   => 'nullable|numeric',
            'diesel_ullage'   => 'nullable|numeric',
            'diesel_water'    => 'nullable|numeric',
            'diesel_temp'     => 'nullable|numeric',
            'diesel_fill'     => 'nullable|numeric',
            'diesel_status'   => 'nullable|string|max:255',
        ]);

        $record = RegularFuelVolume::create($request->all());

        return response()->json([
            'message' => 'Regular fuel volume created successfully',
            'data'    => $record,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, RegularFuelVolume $regularFuelVolume)
    {
        return response()->json($regularFuelVolume);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RegularFuelVolume $regularFuelVolume)
    {
        $user = $request->user();

        if (!$user->canUpdate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'datetime'        => 'required|date',
            'regular_volume'  => 'nullable|numeric',
            'regular_height'  => 'nullable|numeric',
            'regular_ullage'  => 'nullable|numeric',
            'regular_water'   => 'nullable|numeric',
            'regular_temp'    => 'nullable|numeric',
            'regular_fill'    => 'nullable|numeric',
            'regular_status'  => 'nullable|string|max:255',
            'premium_volume'  => 'nullable|numeric',
            'premium_height'  => 'nullable|numeric',
            'premium_ullage'  => 'nullable|numeric',
            'premium_water'   => 'nullable|numeric',
            'premium_temp'    => 'nullable|numeric',
            'premium_fill'    => 'nullable|numeric',
            'premium_status'  => 'nullable|string|max:255',
            'diesel_volume'   => 'nullable|numeric',
            'diesel_height'   => 'nullable|numeric',
            'diesel_ullage'   => 'nullable|numeric',
            'diesel_water'    => 'nullable|numeric',
            'diesel_temp'     => 'nullable|numeric',
            'diesel_fill'     => 'nullable|numeric',
            'diesel_status'   => 'nullable|string|max:255',
        ]);

        $regularFuelVolume->update($request->all());

        return response()->json([
            'message' => 'Regular fuel volume updated successfully',
            'data'    => $regularFuelVolume,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, RegularFuelVolume $regularFuelVolume)
    {
        $user = $request->user();

        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $regularFuelVolume->delete();

        return response()->json([
            'message' => 'Regular fuel volume deleted successfully',
        ]);
    }

    /**
     * Get records for a specific month
     */
    public function getByMonth(Request $request, $year = null, $month = null)
    {
        $year  = $year  ?: TimezoneUtil::now()->format('Y');
        $month = $month ?: TimezoneUtil::now()->format('n');

        $records = RegularFuelVolume::byMonth($year, $month)
            ->orderBy('datetime', 'asc')
            ->get();

        return response()->json([
            'data'  => $records,
            'year'  => $year,
            'month' => $month,
        ]);
    }

    /**
     * Get records for a specific date
     */
    public function getByDate(Request $request, $date = null)
    {
        $date = $date ?: TimezoneUtil::now()->format('Y-m-d');

        $records = RegularFuelVolume::byDate($date)
            ->orderBy('datetime', 'asc')
            ->get();

        return response()->json([
            'data' => $records,
            'date' => $date,
        ]);
    }
}
