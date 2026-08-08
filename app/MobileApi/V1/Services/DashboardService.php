<?php

namespace App\MobileApi\V1\Services;

use App\CashRegister;
use App\Transaction;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected AccessContext $access,
        protected TransactionUtil $transactionUtil
    ) {
    }

    public function build(
        int $businessId,
        array $range,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $current = $this->metrics(
            $businessId,
            $range['from'],
            $range['to'],
            $permittedLocationIds,
            $locationId
        );
        $previous = $this->metrics(
            $businessId,
            $range['previous_from'],
            $range['previous_to'],
            $permittedLocationIds,
            $locationId
        );

        foreach (['net_sales', 'gross_profit', 'expenses', 'collected', 'sales_count'] as $key) {
            $current[$key.'_change_percent'] = $this->percentChange($current[$key], $previous[$key]);
        }

        return [
            'metrics' => $current,
            'comparison' => $previous,
            'trend' => $this->salesTrend(
                $businessId,
                $range['from'],
                $range['to'],
                $permittedLocationIds,
                $locationId
            ),
            'payment_methods' => $this->paymentMethods(
                $businessId,
                $range['from'],
                $range['to'],
                $permittedLocationIds,
                $locationId
            ),
            'top_products' => $this->topProducts(
                $businessId,
                $range['from'],
                $range['to'],
                $permittedLocationIds,
                $locationId
            ),
            'open_registers' => $this->openRegisters($businessId, $permittedLocationIds, $locationId),
            'alerts' => $this->alerts($businessId, $permittedLocationIds, $locationId),
            'recent_transactions' => $this->recentTransactions($businessId, $permittedLocationIds, $locationId),
        ];
    }

    protected function metrics(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $sales = $this->transactionQuery(
            $businessId,
            $from,
            $to,
            $permittedLocationIds,
            $locationId
        )->where('type', 'sell')->where('status', 'final');

        $salesTotal = (float) (clone $sales)->sum('final_total');
        $salesCount = (int) (clone $sales)->count();

        $returns = $this->transactionQuery(
            $businessId,
            $from,
            $to,
            $permittedLocationIds,
            $locationId
        )->where('type', 'sell_return');
        $returnTotal = (float) $returns->sum('final_total');

        $purchases = $this->transactionQuery(
            $businessId,
            $from,
            $to,
            $permittedLocationIds,
            $locationId
        )->where('type', 'purchase')->where('status', 'received');

        $expenses = $this->transactionQuery(
            $businessId,
            $from,
            $to,
            $permittedLocationIds,
            $locationId
        )->where('type', 'expense')->where('status', 'final');

        $collected = $this->paymentTotal(
            $businessId,
            $from,
            $to,
            $permittedLocationIds,
            $locationId,
            ['sell']
        );

        $salePayments = $this->allPaymentsForTransactions(
            $businessId,
            $from,
            $to,
            $permittedLocationIds,
            $locationId,
            ['sell']
        );

        $grossProfit = 0.0;
        if (! empty($permittedLocationIds)) {
            $grossProfit = (float) $this->transactionUtil->getGrossProfit(
                $businessId,
                $from->toDateString(),
                $to->toDateString(),
                $locationId,
                null,
                $permittedLocationIds
            );
        }

        $expenseTotal = (float) $expenses->sum('final_total');

        return [
            'sales' => round($salesTotal, 4),
            'returns' => round($returnTotal, 4),
            'net_sales' => round($salesTotal - $returnTotal, 4),
            'sales_count' => $salesCount,
            'average_ticket' => $salesCount > 0 ? round(($salesTotal - $returnTotal) / $salesCount, 4) : 0,
            'gross_profit' => round($grossProfit, 4),
            'net_profit' => round($grossProfit - $expenseTotal, 4),
            'purchases' => round((float) $purchases->sum('final_total'), 4),
            'expenses' => round($expenseTotal, 4),
            'collected' => round($collected, 4),
            'sales_due' => round(max(0, $salesTotal - $salePayments), 4),
        ];
    }

    protected function transactionQuery(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId
    ) {
        $query = Transaction::query()
            ->where('business_id', $businessId)
            ->whereBetween('transaction_date', [$from, $to]);

        return $this->access->applyLocationScope(
            $query,
            'location_id',
            $permittedLocationIds,
            $locationId
        );
    }

    protected function paymentTotal(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId,
        array $transactionTypes
    ): float {
        $query = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', $transactionTypes)
            ->whereBetween('tp.paid_on', [$from, $to]);

        $this->access->applyLocationScope($query, 't.location_id', $permittedLocationIds, $locationId);

        return (float) $query->sum(DB::raw('IF(tp.is_return = 1, -tp.amount, tp.amount)'));
    }

    protected function allPaymentsForTransactions(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId,
        array $transactionTypes
    ): float {
        $query = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', $transactionTypes)
            ->whereBetween('t.transaction_date', [$from, $to]);

        $this->access->applyLocationScope($query, 't.location_id', $permittedLocationIds, $locationId);

        return (float) $query->sum(DB::raw('IF(tp.is_return = 1, -tp.amount, tp.amount)'));
    }

    protected function salesTrend(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$from, $to]);
        $this->access->applyLocationScope($query, 'location_id', $permittedLocationIds, $locationId);

        $rows = $query
            ->selectRaw('DATE(transaction_date) as period, COUNT(*) as transaction_count, SUM(final_total) as total')
            ->groupByRaw('DATE(transaction_date)')
            ->orderBy('period')
            ->get();

        return $rows->map(fn ($row) => [
            'period' => $row->period,
            'total' => round((float) $row->total, 4),
            'count' => (int) $row->transaction_count,
        ])->all();
    }

    protected function paymentMethods(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $query = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('tp.paid_on', [$from, $to]);
        $this->access->applyLocationScope($query, 't.location_id', $permittedLocationIds, $locationId);

        return $query
            ->selectRaw('tp.method, SUM(IF(tp.is_return = 1, -tp.amount, tp.amount)) as total')
            ->groupBy('tp.method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'total' => round((float) $row->total, 4),
            ])
            ->all();
    }

    protected function topProducts(
        int $businessId,
        Carbon $from,
        Carbon $to,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $query = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'tsl.variation_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$from, $to])
            ->where(function ($nested) {
                $nested->whereNull('tsl.children_type')->orWhere('tsl.children_type', '!=', 'combo');
            });
        $this->access->applyLocationScope($query, 't.location_id', $permittedLocationIds, $locationId);

        return $query
            ->selectRaw(
                'p.id, p.name, v.name as variation_name, SUM(tsl.quantity - tsl.quantity_returned) as quantity, SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax) as total'
            )
            ->groupBy('p.id', 'p.name', 'v.name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'product_id' => (int) $row->id,
                'name' => $row->name,
                'variation' => $row->variation_name,
                'quantity' => round((float) $row->quantity, 4),
                'total' => round((float) $row->total, 4),
            ])
            ->all();
    }

    protected function openRegisters(
        int $businessId,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $query = CashRegister::query()
            ->join('users as u', 'u.id', '=', 'cash_registers.user_id')
            ->join('business_locations as bl', 'bl.id', '=', 'cash_registers.location_id')
            ->where('cash_registers.business_id', $businessId)
            ->where('cash_registers.status', 'open');
        $this->access->applyLocationScope(
            $query,
            'cash_registers.location_id',
            $permittedLocationIds,
            $locationId
        );

        return $query
            ->select([
                'cash_registers.id',
                'cash_registers.created_at as opened_at',
                'u.id as user_id',
                'u.first_name',
                'u.last_name',
                'bl.id as location_id',
                'bl.name as location_name',
            ])
            ->orderByDesc('cash_registers.created_at')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'opened_at' => Carbon::parse($row->opened_at)->toIso8601String(),
                'user' => [
                    'id' => (int) $row->user_id,
                    'name' => trim($row->first_name.' '.$row->last_name),
                ],
                'location' => [
                    'id' => (int) $row->location_id,
                    'name' => $row->location_name,
                ],
            ])
            ->all();
    }

    protected function alerts(int $businessId, array $permittedLocationIds, ?int $locationId): array
    {
        $stockQuery = DB::table('variation_location_details as vld')
            ->join('products as p', 'p.id', '=', 'vld.product_id')
            ->where('p.business_id', $businessId)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereColumn('vld.qty_available', '<=', 'p.alert_quantity');
        $this->access->applyLocationScope($stockQuery, 'vld.location_id', $permittedLocationIds, $locationId);

        $overdueQuery = Transaction::query()
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->overDue();
        $this->access->applyLocationScope($overdueQuery, 'location_id', $permittedLocationIds, $locationId);

        return [
            'low_stock_count' => (int) $stockQuery->count(),
            'overdue_sales_count' => (int) $overdueQuery->count(),
        ];
    }

    protected function recentTransactions(
        int $businessId,
        array $permittedLocationIds,
        ?int $locationId
    ): array {
        $query = Transaction::query()
            ->with(['contact:id,name,supplier_business_name', 'location:id,name', 'sales_person:id,first_name,last_name'])
            ->where('business_id', $businessId)
            ->whereIn('type', ['sell', 'purchase', 'expense', 'sell_return', 'purchase_return'])
            ->orderByDesc('transaction_date');
        $this->access->applyLocationScope($query, 'location_id', $permittedLocationIds, $locationId);

        return $query->limit(8)->get()->map(fn (Transaction $transaction) => [
            'id' => (int) $transaction->id,
            'type' => $transaction->type,
            'reference' => $transaction->invoice_no ?: $transaction->ref_no,
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'amount' => round((float) $transaction->final_total, 4),
            'occurred_at' => Carbon::parse($transaction->transaction_date)->toIso8601String(),
            'contact' => optional($transaction->contact)->supplier_business_name ?: optional($transaction->contact)->name,
            'location' => optional($transaction->location)->name,
            'user' => trim(optional($transaction->sales_person)->first_name.' '.optional($transaction->sales_person)->last_name),
        ])->all();
    }

    protected function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }
}
