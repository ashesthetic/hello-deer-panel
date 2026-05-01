<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NaxmlImport;
use App\Models\PosTransactionItem;
use App\Models\PosTransaction;
use App\Models\PbSku;
use App\Models\PbDepartment;
use App\Services\NaxmlImporterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NaxmlImporterController extends Controller
{
    public function __construct(private NaxmlImporterService $importer) {}

    public function import(Request $request): JsonResponse
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $force = (bool) $request->input('force', false);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['error' => 'Invalid date format. Expected YYYY-MM-DD.'], 422);
        }

        $results = $this->importer->importForDate($date, $force);

        if (isset($results['error'])) {
            return response()->json(['error' => $results['error']], 404);
        }

        return response()->json(['date' => $date, ...$results]);
    }

    public function imports(Request $request): JsonResponse
    {
        $imports = NaxmlImport::orderBy('business_date', 'desc')
            ->orderBy('imported_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($imports);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to', Carbon::today()->toDateString());
        $limit = min((int) $request->input('limit', 20), 100);
        $order = $request->input('order', 'desc') === 'asc' ? 'asc' : 'desc';
        $sortBy = $request->input('sort_by', 'qty') === 'revenue' ? 'total_revenue' : 'total_qty';
        $departmentNumber = $request->input('department_number');
        $excludeDepartments = array_values(array_filter(explode(',', $request->input('exclude_departments', ''))));

        if (!$from) {
            return response()->json(['error' => 'The from parameter is required.'], 422);
        }

        $results = PosTransactionItem::query()
            ->selectRaw('
                pti.plu_code,
                pti.item_number,
                s.english_description,
                d.description AS department,
                SUM(pti.quantity) AS total_qty,
                SUM(pti.sales_amount) AS total_revenue
            ')
            ->from('pos_transaction_items AS pti')
            ->join('pos_transactions AS pt', 'pt.id', '=', 'pti.pos_transaction_id')
            ->leftJoin('pb_skus AS s', 's.item_number', '=', 'pti.item_number')
            ->leftJoin('pb_departments AS d', 'd.department_number', '=', 'pti.department_number')
            ->whereBetween('pt.business_date', [$from, $to])
            ->where('pt.is_training', false)
            ->where('pt.is_suspended', false)
            ->whereNotNull('pti.item_number')
            ->when($departmentNumber, fn($q) => $q->where('pti.department_number', $departmentNumber))
            ->when($excludeDepartments, fn($q) => $q->whereNotIn('pti.department_number', $excludeDepartments))
            ->groupByRaw('pti.plu_code, pti.item_number, s.english_description, d.description')
            ->orderBy($sortBy, $order)
            ->limit($limit)
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'order' => $order,
            'limit' => $limit,
            'data' => $results,
        ]);
    }

    public function pbDepartments(): JsonResponse
    {
        $departments = \App\Models\PbDepartment::orderBy('description')->get(['department_number', 'description']);
        return response()->json($departments);
    }

    public function productsByDepartment(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to', Carbon::today()->toDateString());
        $excludeDepartments = array_values(array_filter(explode(',', $request->input('exclude_departments', ''))));

        if (!$from) {
            return response()->json(['error' => 'The from parameter is required.'], 422);
        }

        $results = PosTransactionItem::query()
            ->selectRaw('
                pti.department_number,
                d.description AS department,
                COUNT(DISTINCT pti.item_number) AS unique_products,
                SUM(pti.quantity) AS total_qty,
                SUM(pti.sales_amount) AS total_revenue
            ')
            ->from('pos_transaction_items AS pti')
            ->join('pos_transactions AS pt', 'pt.id', '=', 'pti.pos_transaction_id')
            ->leftJoin('pb_departments AS d', 'd.department_number', '=', 'pti.department_number')
            ->whereBetween('pt.business_date', [$from, $to])
            ->where('pt.is_training', false)
            ->where('pt.is_suspended', false)
            ->whereNotNull('pti.item_number')
            ->when($excludeDepartments, fn($q) => $q->whereNotIn('pti.department_number', $excludeDepartments))
            ->groupByRaw('pti.department_number, d.description')
            ->orderByDesc('total_revenue')
            ->get();

        $totalRevenue = $results->sum('total_revenue');

        return response()->json([
            'from' => $from,
            'to'   => $to,
            'data' => $results->map(fn($r) => [
                ...$r->toArray(),
                'revenue_pct' => $totalRevenue > 0
                    ? round($r->total_revenue / $totalRevenue * 100, 1) : 0,
            ]),
        ]);
    }
}
