<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelDelivery;
use Illuminate\Http\Request;

class FuelDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $sortBy = $request->input('sort_by', 'delivery_date');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSortFields = ['delivery_date', 'invoice_number', 'total', 'amount', 'issued', 'resolved', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'delivery_date';
        }

        $query = FuelDelivery::query()->orderBy($sortBy, $sortDirection);

        $records = $query->paginate($perPage);

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'delivery_date'  => 'required|date',
            'invoice_number' => 'nullable|string|max:255',
            'regular'        => 'nullable|numeric|min:0',
            'premium'        => 'nullable|numeric|min:0',
            'diesel'         => 'nullable|numeric|min:0',
        ]);

        $regular = (float) ($request->input('regular', 0) ?? 0);
        $premium = (float) ($request->input('premium', 0) ?? 0);
        $diesel  = (float) ($request->input('diesel', 0) ?? 0);
        $total   = $regular + $premium + $diesel;
        $amount  = ($total * 0.03) * 1.05;

        $record = FuelDelivery::create([
            'delivery_date'  => $request->input('delivery_date'),
            'invoice_number' => $request->input('invoice_number'),
            'regular'        => $regular ?: null,
            'premium'        => $premium ?: null,
            'diesel'         => $diesel ?: null,
            'total'          => $total,
            'amount'         => $amount,
            'issued'         => false,
            'issued_date'    => null,
            'resolved'       => false,
            'resolved_date'  => null,
            'note'           => null,
        ]);

        return response()->json([
            'message' => 'Fuel delivery created successfully',
            'data'    => $record,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $record = FuelDelivery::findOrFail($id);
        return response()->json(['data' => $record]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->canUpdate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $record = FuelDelivery::findOrFail($id);

        // issued/resolved fields are admin-only
        $issuedResolvedFields = ['issued', 'issued_date', 'resolved', 'resolved_date'];
        $requestingIssuedResolved = count(array_intersect($issuedResolvedFields, array_keys($request->all()))) > 0;

        if ($requestingIssuedResolved && !$user->canManageUsers()) {
            return response()->json(['message' => 'Only admins can update issued/resolved fields'], 403);
        }

        $request->validate([
            'delivery_date'  => 'sometimes|date',
            'invoice_number' => 'nullable|string|max:255',
            'regular'        => 'nullable|numeric|min:0',
            'premium'        => 'nullable|numeric|min:0',
            'diesel'         => 'nullable|numeric|min:0',
            'issued'         => 'sometimes|boolean',
            'issued_date'    => 'nullable|date',
            'resolved'       => 'sometimes|boolean',
            'resolved_date'  => 'nullable|date',
            'note'           => 'nullable|string',
        ]);

        $data = $request->only([
            'delivery_date', 'invoice_number', 'regular', 'premium', 'diesel',
            'issued', 'issued_date', 'resolved', 'resolved_date', 'note',
        ]);

        // Recalculate total/amount if volume fields changed
        if (isset($data['regular']) || isset($data['premium']) || isset($data['diesel'])) {
            $regular = (float) ($data['regular'] ?? $record->regular ?? 0);
            $premium = (float) ($data['premium'] ?? $record->premium ?? 0);
            $diesel  = (float) ($data['diesel']  ?? $record->diesel  ?? 0);
            $data['total']  = $regular + $premium + $diesel;
            $data['amount'] = ($data['total'] * 0.03) * 1.05;
        }

        $record->update($data);

        return response()->json([
            'message' => 'Fuel delivery updated successfully',
            'data'    => $record->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $record = FuelDelivery::findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Fuel delivery deleted successfully']);
    }
}
