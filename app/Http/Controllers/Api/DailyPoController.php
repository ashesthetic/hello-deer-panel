<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\DailyPo;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyPoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DailyPo::query();

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $allowedSortFields = ['date', 'amount', 'resolved'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date'   => 'required|date|unique:daily_pos,date',
            'amount' => 'required|numeric|min:0',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $dailyPo = DailyPo::create([
            'date'   => $request->date,
            'amount' => $request->amount,
            'notes'  => $request->notes,
        ]);

        return response()->json([
            'message' => 'Daily POS record created successfully',
            'data'    => $dailyPo,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, DailyPo $dailyPo)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $dailyPo]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyPo $dailyPo)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date'   => 'sometimes|required|date|unique:daily_pos,date,' . $dailyPo->id,
            'amount' => 'sometimes|required|numeric|min:0',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $dailyPo->update($request->only(['date', 'amount', 'notes']));

        return response()->json([
            'message' => 'Daily POS record updated successfully',
            'data'    => $dailyPo,
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Request $request, DailyPo $dailyPo)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dailyPo->delete();

        return response()->json(['message' => 'Daily POS record deleted successfully']);
    }

    /**
     * Resolve a daily POS record.
     */
    public function resolve(Request $request, DailyPo $dailyPo)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($dailyPo->resolved) {
            return response()->json(['message' => 'POS record is already resolved'], 400);
        }

        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount'          => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $bankAccount = BankAccount::findOrFail($request->bank_account_id);

            if (!$bankAccount->is_active) {
                throw new \Exception('Bank account is not active');
            }

            $formattedDate = Carbon::parse($dailyPo->date)->format('F d, Y');

            Transaction::create([
                'type'             => 'income',
                'amount'           => $request->amount,
                'description'      => "POS Resolution - {$formattedDate}",
                'notes'            => $request->notes ?? "Resolved POS entry from {$formattedDate}",
                'bank_account_id'  => $bankAccount->id,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'POS-RES-' . Carbon::parse($dailyPo->date)->format('Ymd'),
                'status'           => 'completed',
                'user_id'          => $user->id,
            ]);

            $bankAccount->increment('balance', $request->amount);

            $dailyPo->update([
                'resolved'        => true,
                'resolved_amount' => $request->amount,
                'notes'           => $request->input('notes', $dailyPo->notes),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Daily POS record resolved successfully',
                'data'    => $dailyPo,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to resolve POS record: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing including soft-deleted records.
     */
    public function withTrashed(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = $request->input('per_page', 10);

        return response()->json(DailyPo::withTrashed()->orderBy('date', 'desc')->paginate($perPage));
    }

    /**
     * Restore a soft-deleted record.
     */
    public function restore(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dailyPo = DailyPo::withTrashed()->findOrFail($id);
        $dailyPo->restore();

        return response()->json([
            'message' => 'Daily POS record restored successfully',
            'data'    => $dailyPo,
        ]);
    }

    /**
     * Permanently delete a record.
     */
    public function forceDelete(Request $request, string $id)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dailyPo = DailyPo::withTrashed()->findOrFail($id);
        $dailyPo->forceDelete();

        return response()->json(['message' => 'Daily POS record permanently deleted']);
    }
}
