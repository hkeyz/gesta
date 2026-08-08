<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\CashRegister;
use App\Http\Controllers\Controller;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Services\TransactionPresenter;
use App\MobileApi\V1\Support\ApiResponse;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    use ApiResponse;

    public function open(Request $request, AccessContext $access)
    {
        if (! $request->user()->can('view_cash_register') && ! $request->user()->can('dashboard.data')) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);
        $query = CashRegister::query()
            ->join('users as u', 'u.id', '=', 'cash_registers.user_id')
            ->join('business_locations as bl', 'bl.id', '=', 'cash_registers.location_id')
            ->where('cash_registers.business_id', $access->businessId($request))
            ->where('cash_registers.status', 'open');
        $access->applyLocationScope($query, 'cash_registers.location_id', $locationIds, $locationId);

        $registers = $query
            ->select([
                'cash_registers.*',
                'u.first_name',
                'u.last_name',
                'bl.name as location_name',
            ])
            ->orderByDesc('cash_registers.created_at')
            ->get()
            ->map(function ($register) {
                $sales = DB::table('transactions')
                    ->where('business_id', $register->business_id)
                    ->where('location_id', $register->location_id)
                    ->where('created_by', $register->user_id)
                    ->where('type', 'sell')
                    ->where('status', 'final')
                    ->where('transaction_date', '>=', $register->created_at)
                    ->selectRaw('COUNT(*) as count, COALESCE(SUM(final_total), 0) as total')
                    ->first();

                $movements = DB::table('cash_register_transactions')
                    ->where('cash_register_id', $register->id)
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) as credits, COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) as debits'
                    )
                    ->first();

                return [
                    'id' => (int) $register->id,
                    'status' => $register->status,
                    'opened_at' => Carbon::parse($register->created_at)->toIso8601String(),
                    'duration_minutes' => Carbon::parse($register->created_at)->diffInMinutes(now()),
                    'user' => [
                        'id' => (int) $register->user_id,
                        'name' => trim($register->first_name.' '.$register->last_name),
                    ],
                    'location' => [
                        'id' => (int) $register->location_id,
                        'name' => $register->location_name,
                    ],
                    'sales' => [
                        'count' => (int) ($sales->count ?? 0),
                        'total' => round((float) ($sales->total ?? 0), 4),
                    ],
                    'cash_movements' => [
                        'credits' => round((float) ($movements->credits ?? 0), 4),
                        'debits' => round((float) ($movements->debits ?? 0), 4),
                        'net' => round(
                            (float) ($movements->credits ?? 0) - (float) ($movements->debits ?? 0),
                            4
                        ),
                    ],
                ];
            })
            ->values();

        return $this->success($registers, $this->realtimeMeta(15));
    }

    public function show(
        Request $request,
        int $register,
        AccessContext $access,
        TransactionPresenter $presenter
    ) {
        if (! $request->user()->can('view_cash_register')
            && ! $request->user()->can('dashboard.data')) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $locationIds = $access->permittedLocationIds($request->user());
        $modelQuery = CashRegister::query()
            ->join('users as u', 'u.id', '=', 'cash_registers.user_id')
            ->join('business_locations as bl', 'bl.id', '=', 'cash_registers.location_id')
            ->where('cash_registers.business_id', $access->businessId($request))
            ->select([
                'cash_registers.*',
                'u.first_name',
                'u.last_name',
                'bl.name as location_name',
            ]);
        $access->applyLocationScope($modelQuery, 'cash_registers.location_id', $locationIds);
        $model = $modelQuery->find($register);
        if (empty($model)) {
            return $this->failure(__('Cash register not found.'), [], 404, 'not_found');
        }

        $movements = DB::table('cash_register_transactions')
            ->where('cash_register_id', $model->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($movement) => [
                'id' => (int) $movement->id,
                'type' => $movement->type,
                'transaction_type' => $movement->transaction_type,
                'payment_method' => $movement->pay_method,
                'amount' => round((float) $movement->amount, 4),
                'transaction_id' => $movement->transaction_id
                    ? (int) $movement->transaction_id
                    : null,
                'occurred_at' => Carbon::parse($movement->created_at)->toIso8601String(),
            ])->values();

        $breakdown = $movements->groupBy('payment_method')
            ->map(function ($items, $method) {
                $credits = $items->where('type', 'credit')->sum('amount');
                $debits = $items->where('type', 'debit')->sum('amount');

                return [
                    'method' => $method,
                    'credits' => round((float) $credits, 4),
                    'debits' => round((float) $debits, 4),
                    'net' => round((float) $credits - (float) $debits, 4),
                ];
            })->values();

        $sales = Transaction::query()
            ->with([
                'contact:id,name,supplier_business_name,mobile',
                'location:id,name',
                'sales_person:id,first_name,last_name',
            ])
            ->where('business_id', $model->business_id)
            ->where('location_id', $model->location_id)
            ->where('created_by', $model->user_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('transaction_date', '>=', $model->created_at)
            ->when(
                $model->status === 'close' && $model->closed_at,
                fn ($query) => $query->where('transaction_date', '<=', $model->closed_at)
            )
            ->orderByDesc('transaction_date')
            ->limit(30)
            ->get()
            ->map(fn (Transaction $transaction) => $presenter->summary($transaction))
            ->values();

        $credits = $movements->where('type', 'credit')->sum('amount');
        $debits = $movements->where('type', 'debit')->sum('amount');

        return $this->success([
            'id' => (int) $model->id,
            'status' => $model->status,
            'opened_at' => Carbon::parse($model->created_at)->toIso8601String(),
            'closed_at' => $model->closed_at
                ? Carbon::parse($model->closed_at)->toIso8601String()
                : null,
            'duration_minutes' => Carbon::parse($model->created_at)
                ->diffInMinutes($model->closed_at ? Carbon::parse($model->closed_at) : now()),
            'user' => [
                'id' => (int) $model->user_id,
                'name' => trim($model->first_name.' '.$model->last_name),
            ],
            'location' => [
                'id' => (int) $model->location_id,
                'name' => $model->location_name,
            ],
            'totals' => [
                'credits' => round((float) $credits, 4),
                'debits' => round((float) $debits, 4),
                'net' => round((float) $credits - (float) $debits, 4),
                'closing_amount' => round((float) $model->closing_amount, 4),
            ],
            'payment_breakdown' => $breakdown,
            'movements' => $movements,
            'recent_sales' => $sales,
        ], $this->realtimeMeta(15));
    }
}
