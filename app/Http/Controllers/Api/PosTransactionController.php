<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PosTransaction::query()
            ->with(['import:id,filename,shift_id,store_location_id'])
            ->withCount('items');

        if ($date = $request->input('date')) {
            $query->whereDate('business_date', $date);
        } elseif ($from = $request->input('from')) {
            $to = $request->input('to', now()->toDateString());
            $query->whereBetween('business_date', [$from, $to]);
        }

        if ($registerId = $request->input('register_id')) {
            $query->where('register_id', $registerId);
        }

        if ($cashierId = $request->input('cashier_id')) {
            $query->where('cashier_id', $cashierId);
        }

        $transactions = $query
            ->orderBy('business_date', 'desc')
            ->orderBy('started_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = PosTransaction::with([
            'import:id,filename,shift_id,store_location_id',
            'items.sku:item_number,english_description,french_description,price',
            'items.department:department_number,description',
            'tenders',
        ])->findOrFail($id);

        return response()->json($transaction);
    }
}
