<?php

namespace Modules\MobileMoney\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\MobileMoney\Entities\MmOperator;
use Modules\MobileMoney\Entities\MmTransaction;

class ReportController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.reports', 'mobile_money.access']);
        $this->ensureBusinessSetup();

        $today = now()->toDateString();
        $filters = [
            'operator_id' => $request->input('operator_id'),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date') ?: $today,
            'end_date' => $request->input('end_date') ?: $today,
        ];

        $baseQuery = MmTransaction::forBusiness($this->businessId());
        $this->mobileMoneyService->applyTransactionFilters($baseQuery, $filters);

        $summaryQuery = clone $baseQuery;
        $summary = [
            'total_operations' => (clone $summaryQuery)->count(),
            'completed_operations' => (clone $summaryQuery)->where('status', 'completed')->count(),
            'cancelled_operations' => (clone $summaryQuery)->where('status', 'cancelled')->count(),
            'deposit_amount' => (clone $summaryQuery)->where('status', 'completed')->where('type', 'deposit')->sum('amount'),
            'withdrawal_amount' => (clone $summaryQuery)->where('status', 'completed')->where('type', 'withdrawal')->sum('amount'),
            'commission_total' => (clone $summaryQuery)->where('status', 'completed')->sum('commission'),
        ];

        $dailyRows = (clone $baseQuery)
            ->select(
                DB::raw('DATE(operation_datetime) as operation_day'),
                DB::raw("SUM(CASE WHEN status = 'completed' AND type = 'deposit' THEN amount ELSE 0 END) as deposit_amount"),
                DB::raw("SUM(CASE WHEN status = 'completed' AND type = 'withdrawal' THEN amount ELSE 0 END) as withdrawal_amount"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN commission ELSE 0 END) as commission_total"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            )
            ->groupBy(DB::raw('DATE(operation_datetime)'))
            ->orderByDesc('operation_day')
            ->get();

        $chartRows = $dailyRows->sortBy('operation_day')->values();
        $chartData = [
            'labels' => $chartRows->pluck('operation_day')->values(),
            'deposit_amounts' => $chartRows->pluck('deposit_amount')->map(fn ($value) => (float) $value)->values(),
            'withdrawal_amounts' => $chartRows->pluck('withdrawal_amount')->map(fn ($value) => (float) $value)->values(),
            'commission_totals' => $chartRows->pluck('commission_total')->map(fn ($value) => (float) $value)->values(),
            'completed_counts' => $chartRows->pluck('completed_count')->map(fn ($value) => (int) $value)->values(),
            'cancelled_counts' => $chartRows->pluck('cancelled_count')->map(fn ($value) => (int) $value)->values(),
        ];

        $operatorRows = (clone $baseQuery)
            ->join('mm_operators as operators', 'mm_transactions.operator_id', '=', 'operators.id')
            ->select(
                'operators.name as operator_name',
                DB::raw("SUM(CASE WHEN mm_transactions.status = 'completed' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN mm_transactions.status = 'completed' AND mm_transactions.type = 'deposit' THEN mm_transactions.amount ELSE 0 END) as deposit_amount"),
                DB::raw("SUM(CASE WHEN mm_transactions.status = 'completed' AND mm_transactions.type = 'withdrawal' THEN mm_transactions.amount ELSE 0 END) as withdrawal_amount"),
                DB::raw("SUM(CASE WHEN mm_transactions.status = 'completed' THEN mm_transactions.commission ELSE 0 END) as commission_total")
            )
            ->groupBy('operators.name')
            ->orderByDesc('commission_total')
            ->get();

        $operators = MmOperator::forBusiness($this->businessId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $transactionTypes = $this->mobileMoneyService->transactionTypes();
        $statuses = $this->mobileMoneyService->statuses();

        return view('mobilemoney::reports.index', compact('summary', 'dailyRows', 'operatorRows', 'operators', 'transactionTypes', 'statuses', 'filters', 'chartData'));
    }
}
