<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Http\Controllers\Controller;
use App\MobileApi\V1\Http\Requests\ActivityRequest;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Services\ActivityService;
use App\MobileApi\V1\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use ApiResponse;

    public function __invoke(ActivityRequest $request, AccessContext $access, ActivityService $activity)
    {
        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);
        $allowedTypes = $this->allowedTypes($request);
        $types = array_values(array_intersect(
            array_filter(explode(',', (string) $request->input('types'))),
            $allowedTypes
        ));

        if (empty($types)) {
            $types = $allowedTypes;
        }

        $data = $activity->feed(
            $access->businessId($request),
            $locationIds,
            $locationId,
            $request->filled('since') ? Carbon::parse($request->input('since')) : null,
            (int) $request->input('after_id', 0),
            $types,
            (int) $request->input('limit', 40),
            (int) $request->user()->id,
            $this->ownedOnlyTypes($request, $types),
            $request->filled('before') ? Carbon::parse($request->input('before')) : null,
            (int) $request->input('before_id', 0)
        );

        return $this->success($data, $this->realtimeMeta(10));
    }

    protected function allowedTypes(Request $request): array
    {
        $user = $request->user();
        if ($user->can('dashboard.data')) {
            return [
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
        }

        $types = [];
        if ($user->can('sell.view') || $user->can('sell.view_own')) {
            $types = array_merge($types, ['sell', 'sales_order', 'sell_return']);
        }
        if ($user->can('purchase.view') || $user->can('view_own_purchase')) {
            $types = array_merge($types, [
                'purchase',
                'purchase_order',
                'purchase_requisition',
                'purchase_return',
            ]);
        }
        if ($user->can('expense.access')) {
            $types = array_merge($types, ['expense', 'expense_refund']);
        }
        if ($user->can('stock_report.view') || $user->can('product.view')) {
            $types = array_merge($types, [
                'stock_adjustment',
                'sell_transfer',
                'purchase_transfer',
                'opening_stock',
            ]);
        }

        return array_values(array_unique($types));
    }

    protected function ownedOnlyTypes(Request $request, array $types): array
    {
        $user = $request->user();
        if ($user->can('dashboard.data')) {
            return [];
        }

        $ownedOnly = [];
        if (! $user->can('sell.view') && $user->can('sell.view_own')) {
            $ownedOnly = array_merge(
                $ownedOnly,
                array_intersect($types, ['sell', 'sales_order', 'sell_return'])
            );
        }
        if (! $user->can('purchase.view') && $user->can('view_own_purchase')) {
            $ownedOnly = array_merge(
                $ownedOnly,
                array_intersect($types, [
                    'purchase',
                    'purchase_order',
                    'purchase_requisition',
                    'purchase_return',
                ])
            );
        }

        return array_values(array_unique($ownedOnly));
    }
}
