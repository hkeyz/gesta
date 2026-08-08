<?php

namespace App\MobileApi\V1\Services;

use App\Transaction;
use Carbon\Carbon;

class ActivityService
{
    public function __construct(
        protected AccessContext $access,
        protected TransactionPresenter $presenter
    ) {
    }

    public function feed(
        int $businessId,
        array $permittedLocationIds,
        ?int $locationId,
        ?Carbon $since,
        int $afterId,
        array $types,
        int $limit,
        int $userId,
        array $ownedOnlyTypes = [],
        ?Carbon $before = null,
        int $beforeId = 0
    ): array {
        $query = Transaction::query()
            ->with(['contact:id,name,supplier_business_name,mobile', 'location:id,name', 'sales_person:id,first_name,last_name'])
            ->where('business_id', $businessId)
            ->whereIn('type', $types);

        $this->access->applyLocationScope($query, 'location_id', $permittedLocationIds, $locationId);
        $this->applyOwnershipScope($query, $types, $ownedOnlyTypes, $userId);

        if (! empty($since)) {
            $query->where(function ($nested) use ($since, $afterId) {
                $nested->where('updated_at', '>', $since)
                    ->orWhere(function ($sameMoment) use ($since, $afterId) {
                        $sameMoment->where('updated_at', '=', $since)
                            ->where('id', '>', $afterId);
                    });
            })->orderBy('updated_at')->orderBy('id');
        } elseif (! empty($before)) {
            $query->where(function ($nested) use ($before, $beforeId) {
                $nested->where('updated_at', '<', $before)
                    ->orWhere(function ($sameMoment) use ($before, $beforeId) {
                        $sameMoment->where('updated_at', '=', $before)
                            ->where('id', '<', $beforeId);
                    });
            })->orderByDesc('updated_at')->orderByDesc('id');
        } else {
            $query->orderByDesc('updated_at')->orderByDesc('id');
        }

        $items = $query->limit($limit)->get();
        $cursorItem = $items->sortBy([
            ['updated_at', 'desc'],
            ['id', 'desc'],
        ])->first();

        return [
            'items' => $items->map(function (Transaction $transaction) {
                $summary = $this->presenter->summary($transaction);

                return array_merge($summary, [
                    'category' => $this->category($transaction->type),
                    'title' => $this->title($transaction),
                    'description' => $this->description($transaction),
                ]);
            })->values(),
            'cursor' => [
                'since' => $cursorItem?->updated_at?->format('Y-m-d H:i:s.u'),
                'after_id' => $cursorItem ? (int) $cursorItem->id : $afterId,
            ],
        ];
    }

    protected function category(string $type): string
    {
        return match ($type) {
            'sell', 'sales_order' => 'sale',
            'purchase', 'purchase_order', 'purchase_requisition' => 'purchase',
            'expense', 'expense_refund' => 'expense',
            'sell_return', 'purchase_return' => 'return',
            'stock_adjustment', 'sell_transfer', 'purchase_transfer', 'opening_stock' => 'stock',
            default => 'transaction',
        };
    }

    protected function title(Transaction $transaction): string
    {
        $labels = [
            'sell' => __('Sale'),
            'sales_order' => __('Sales order'),
            'purchase' => __('Purchase'),
            'purchase_order' => __('Purchase order'),
            'purchase_requisition' => __('Purchase requisition'),
            'expense' => __('Expense'),
            'expense_refund' => __('Expense refund'),
            'sell_return' => __('Sale return'),
            'purchase_return' => __('Purchase return'),
            'stock_adjustment' => __('Stock adjustment'),
            'sell_transfer' => __('Stock transfer'),
            'purchase_transfer' => __('Stock transfer receipt'),
            'opening_stock' => __('Opening stock'),
        ];

        return ($labels[$transaction->type] ?? ucfirst(str_replace('_', ' ', $transaction->type)))
            .' · '.($transaction->invoice_no ?: $transaction->ref_no ?: '#'.$transaction->id);
    }

    protected function description(Transaction $transaction): string
    {
        $parts = array_filter([
            optional($transaction->contact)->supplier_business_name ?: optional($transaction->contact)->name,
            optional($transaction->location)->name,
            trim(optional($transaction->sales_person)->first_name.' '.optional($transaction->sales_person)->last_name),
        ]);

        return implode(' · ', $parts);
    }

    protected function applyOwnershipScope(
        $query,
        array $types,
        array $ownedOnlyTypes,
        int $userId
    ): void {
        if (empty($ownedOnlyTypes)) {
            return;
        }

        $unrestrictedTypes = array_values(array_diff($types, $ownedOnlyTypes));
        $query->where(function ($scope) use ($unrestrictedTypes, $ownedOnlyTypes, $userId) {
            if (! empty($unrestrictedTypes)) {
                $scope->whereIn('type', $unrestrictedTypes);
            }

            $method = empty($unrestrictedTypes) ? 'where' : 'orWhere';
            $scope->{$method}(function ($owned) use ($ownedOnlyTypes, $userId) {
                $owned->whereIn('type', $ownedOnlyTypes)
                    ->where('created_by', $userId);
            });
        });
    }
}
