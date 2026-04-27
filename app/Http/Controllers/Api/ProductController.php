<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'item_number');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSortFields = ['id', 'item_number', 'name', 'price', 'department_number', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'item_number';
        }

        $query = Product::with('department');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('item_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('department_number')) {
            $query->where('department_number', $request->department_number);
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_number' => 'required|string|max:255|unique:products,item_number',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'department_number' => 'required|integer|exists:departments,department_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->only(['item_number', 'name', 'price', 'department_number']));
        $product->load('department');

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    public function show(string $id)
    {
        $product = Product::with(['department', 'itemSales'])->findOrFail($id);

        return response()->json([
            'data' => $product
        ]);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_number' => 'sometimes|required|string|max:255|unique:products,item_number,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'department_number' => 'sometimes|required|integer|exists:departments,department_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->only(['item_number', 'name', 'price', 'department_number']));
        $product->load('department');

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function restore(string $id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        if (!$product->trashed()) {
            return response()->json(['message' => 'Product is not deleted'], 400);
        }

        $product->restore();
        $product->load('department');

        return response()->json([
            'message' => 'Product restored successfully',
            'data' => $product
        ]);
    }

    public function forceDelete(string $id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();

        return response()->json([
            'message' => 'Product permanently deleted'
        ]);
    }
}
