<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Http\Controllers\Controller;
use App\MobileApi\V1\Http\Requests\ListRequest;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Services\TransactionPresenter;
use App\MobileApi\V1\Support\ApiResponse;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(ListRequest $request, AccessContext $access, TransactionPresenter $presenter)
    {
        return $this->transactionList($request, $access, $presenter);
    }

    public function sales(ListRequest $request, AccessContext $access, TransactionPresenter $presenter)
    {
        $request->merge(['type' => 'sell']);

        return $this->transactionList($request, $access, $presenter);
    }

    public function purchases(ListRequest $request, AccessContext $access, TransactionPresenter $presenter)
    {
        $request->merge(['type' => 'purchase']);

        return $this->transactionList($request, $access, $presenter);
    }

    public function expenses(ListRequest $request, AccessContext $access, TransactionPresenter $presenter)
    {
        $request->merge(['type' => 'expense']);

        return $this->transactionList($request, $access, $presenter);
    }

    public function show(
        Request $request,
        int $transaction,
        AccessContext $access,
        TransactionPresenter $presenter
    ) {
        $locationIds = $access->permittedLocationIds($request->user());
        $query = Transaction::query()
            ->with([
                'contact:id,name,supplier_business_name,mobile',
                'location:id,name',
                'sales_person:id,first_name,last_name',
                'payment_lines',
                'sell_lines.product:id,name',
                'sell_lines.variations:id,name,sub_sku',
                'return_parent_sell:id,invoice_no,ref_no,final_total',
                'return_parent_sell.sell_lines.product:id,name',
                'return_parent_sell.sell_lines.variations:id,name,sub_sku',
                'purchase_lines.product:id,name',
                'purchase_lines.variations:id,name,sub_sku',
            ])
            ->where('business_id', $access->businessId($request));
        $access->applyLocationScope($query, 'location_id', $locationIds);

        $model = $query->find($transaction);
        if (empty($model)) {
            return $this->failure(__('Transaction not found.'), [], 404, 'not_found');
        }

        if (! $this->canViewType($request, $model->type)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }
        if (! $this->canViewTransaction($request, $model)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        return $this->success($presenter->detail($model), $this->realtimeMeta());
    }

    protected function transactionList(
        ListRequest $request,
        AccessContext $access,
        TransactionPresenter $presenter
    ) {
        $types = $this->resolveTypes($request);
        if (! $this->canViewTypes($request, $types)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);
        $query = Transaction::query()
            ->with([
                'contact:id,name,supplier_business_name,mobile',
                'location:id,name',
                'sales_person:id,first_name,last_name',
            ])
            ->where('business_id', $access->businessId($request))
            ->whereIn('type', $types);

        $access->applyLocationScope($query, 'location_id', $locationIds, $locationId);
        $this->applyOwnershipScope($query, $request, $types);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('from')) {
            $query->where('transaction_date', '>=', Carbon::parse($request->input('from'))->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('transaction_date', '<=', Carbon::parse($request->input('to'))->endOfDay());
        }
        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($nested) use ($search) {
                $nested->where('invoice_no', 'like', $search)
                    ->orWhere('ref_no', 'like', $search)
                    ->orWhereHas('contact', function ($contact) use ($search) {
                        $contact->where('name', 'like', $search)
                            ->orWhere('supplier_business_name', 'like', $search)
                            ->orWhere('mobile', 'like', $search);
                    });
            });
        }

        $paginator = $query
            ->orderByDesc('transaction_date')
            ->paginate((int) $request->input('per_page', 20));

        return $this->success(
            $paginator->getCollection()->map(fn ($transaction) => $presenter->summary($transaction))->values(),
            array_merge($this->realtimeMeta(), [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ])
        );
    }

    protected function resolveTypes(ListRequest $request): array
    {
        $allowed = [
            'sell',
            'sales_order',
            'purchase',
            'purchase_order',
            'purchase_requisition',
            'expense',
            'expense_refund',
            'sell_return',
            'purchase_return',
            'stock_adjustment',
            'sell_transfer',
            'purchase_transfer',
            'opening_stock',
        ];
        $requested = array_filter(explode(',', (string) $request->input('type')));
        $types = array_values(array_intersect($requested, $allowed));

        return $types ?: $allowed;
    }

    protected function canViewTypes(Request $request, array $types): bool
    {
        foreach ($types as $type) {
            if (! $this->canViewType($request, $type)) {
                return false;
            }
        }

        return true;
    }

    protected function canViewType(Request $request, string $type): bool
    {
        $user = $request->user();

        if (in_array($type, ['sell', 'sales_order', 'sell_return'], true)) {
            return $user->can('sell.view') || $user->can('sell.view_own') || $user->can('dashboard.data');
        }
        if (in_array($type, ['purchase', 'purchase_order', 'purchase_requisition', 'purchase_return'], true)) {
            return $user->can('purchase.view') || $user->can('view_own_purchase') || $user->can('dashboard.data');
        }
        if (in_array($type, ['expense', 'expense_refund'], true)) {
            return $user->can('expense.access') || $user->can('dashboard.data');
        }

        return $user->can('stock_report.view') || $user->can('dashboard.data');
    }

    protected function applyOwnershipScope($query, Request $request, array $types): void
    {
        $user = $request->user();
        if ($user->can('dashboard.data')) {
            return;
        }

        $ownedOnlyTypes = [];
        if (! $user->can('sell.view') && $user->can('sell.view_own')) {
            $ownedOnlyTypes = array_merge(
                $ownedOnlyTypes,
                array_intersect($types, ['sell', 'sales_order', 'sell_return'])
            );
        }
        if (! $user->can('purchase.view') && $user->can('view_own_purchase')) {
            $ownedOnlyTypes = array_merge(
                $ownedOnlyTypes,
                array_intersect($types, [
                    'purchase',
                    'purchase_order',
                    'purchase_requisition',
                    'purchase_return',
                ])
            );
        }

        if (empty($ownedOnlyTypes)) {
            return;
        }

        $unrestrictedTypes = array_values(array_diff($types, $ownedOnlyTypes));
        $query->where(function ($scope) use ($unrestrictedTypes, $ownedOnlyTypes, $user) {
            if (! empty($unrestrictedTypes)) {
                $scope->whereIn('type', $unrestrictedTypes);
            }

            $method = empty($unrestrictedTypes) ? 'where' : 'orWhere';
            $scope->{$method}(function ($owned) use ($ownedOnlyTypes, $user) {
                $owned->whereIn('type', $ownedOnlyTypes)
                    ->where('created_by', $user->id);
            });
        });
    }

    protected function canViewTransaction(Request $request, Transaction $transaction): bool
    {
        $user = $request->user();
        if ($user->can('dashboard.data')) {
            return true;
        }

        if (in_array($transaction->type, ['sell', 'sales_order', 'sell_return'], true)
            && ! $user->can('sell.view')) {
            return $user->can('sell.view_own')
                && (int) $transaction->created_by === (int) $user->id;
        }

        if (in_array($transaction->type, [
            'purchase',
            'purchase_order',
            'purchase_requisition',
            'purchase_return',
        ], true) && ! $user->can('purchase.view')) {
            return $user->can('view_own_purchase')
                && (int) $transaction->created_by === (int) $user->id;
        }

        return true;
    }
}
