<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemSaleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSortFields = ['id', 'item_number', 'name', 'qty', 'price', 'date', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }

        $query = ItemSale::with(['product', 'department']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('item_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('item_number')) {
            $query->where('item_number', $request->item_number);
        }

        if ($request->filled('department_number')) {
            $query->where('department_number', $request->department_number);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_number' => 'required|string|max:255|exists:products,item_number',
            'name' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $itemSale = ItemSale::create($request->only(['item_number', 'department_number', 'name', 'qty', 'price', 'date']));
        $itemSale->load(['product', 'department']);

        return response()->json([
            'message' => 'Item sale created successfully',
            'data' => $itemSale
        ], 201);
    }

    public function show(string $id)
    {
        $itemSale = ItemSale::with(['product', 'department'])->findOrFail($id);

        return response()->json([
            'data' => $itemSale
        ]);
    }

    public function update(Request $request, string $id)
    {
        $itemSale = ItemSale::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_number' => 'sometimes|required|string|max:255|exists:products,item_number',
            'name' => 'sometimes|required|string|max:255',
            'qty' => 'sometimes|required|numeric|min:0',
            'price' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $itemSale->update($request->only(['item_number', 'department_number', 'name', 'qty', 'price', 'date']));
        $itemSale->load(['product', 'department']);

        return response()->json([
            'message' => 'Item sale updated successfully',
            'data' => $itemSale
        ]);
    }

    public function destroy(string $id)
    {
        $itemSale = ItemSale::findOrFail($id);
        $itemSale->delete();

        return response()->json([
            'message' => 'Item sale deleted successfully'
        ]);
    }

    public function restore(string $id)
    {
        $itemSale = ItemSale::withTrashed()->findOrFail($id);

        if (!$itemSale->trashed()) {
            return response()->json(['message' => 'Item sale is not deleted'], 400);
        }

        $itemSale->restore();
        $itemSale->load(['product', 'department']);

        return response()->json([
            'message' => 'Item sale restored successfully',
            'data' => $itemSale
        ]);
    }

    public function forceDelete(string $id)
    {
        $itemSale = ItemSale::withTrashed()->findOrFail($id);
        $itemSale->forceDelete();

        return response()->json([
            'message' => 'Item sale permanently deleted'
        ]);
    }
}
