<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Http\Controllers\Controller;
use App\MobileApi\V1\Http\Requests\ListRequest;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use ApiResponse;

    public function summary(Request $request, AccessContext $access)
    {
        if (! $this->canView($request)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);
        $query = $this->baseVariationQuery(
            $access->businessId($request),
            $locationIds,
            $locationId,
            $access
        );

        $row = DB::query()
            ->fromSub($query, 'inventory')
            ->selectRaw(
                'COUNT(*) as variation_count, SUM(CASE WHEN enable_stock = 1 THEN stock ELSE 0 END) as units_in_stock, SUM(CASE WHEN enable_stock = 1 THEN stock * purchase_price ELSE 0 END) as cost_value, SUM(CASE WHEN enable_stock = 1 THEN stock * sell_price ELSE 0 END) as retail_value, SUM(CASE WHEN enable_stock = 1 AND stock <= alert_quantity THEN 1 ELSE 0 END) as low_stock_count, SUM(CASE WHEN enable_stock = 1 AND stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count'
            )
            ->first();

        return $this->success([
            'variation_count' => (int) ($row->variation_count ?? 0),
            'units_in_stock' => round((float) ($row->units_in_stock ?? 0), 4),
            'cost_value' => round((float) ($row->cost_value ?? 0), 4),
            'retail_value' => round((float) ($row->retail_value ?? 0), 4),
            'potential_margin' => round(
                (float) ($row->retail_value ?? 0) - (float) ($row->cost_value ?? 0),
                4
            ),
            'low_stock_count' => (int) ($row->low_stock_count ?? 0),
            'out_of_stock_count' => (int) ($row->out_of_stock_count ?? 0),
            'location_id' => $locationId,
        ], $this->realtimeMeta(20));
    }

    public function products(ListRequest $request, AccessContext $access)
    {
        if (! $this->canView($request)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        return $this->productList($request, $access, false);
    }

    public function lowStock(ListRequest $request, AccessContext $access)
    {
        if (! $this->canView($request)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        return $this->productList($request, $access, true);
    }

    public function categories(Request $request, AccessContext $access)
    {
        if (! $this->canView($request)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $rows = DB::table('categories')
            ->where('business_id', $access->businessId($request))
            ->where('category_type', 'product')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'short_code', 'parent_id']);
        $children = $rows->filter(fn ($row) => (int) $row->parent_id > 0)
            ->groupBy(fn ($row) => (int) $row->parent_id);
        $items = $rows->filter(fn ($row) => (int) $row->parent_id === 0)
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'short_code' => $row->short_code,
                'children' => $children->get((int) $row->id, collect())
                    ->map(fn ($child) => [
                        'id' => (int) $child->id,
                        'name' => $child->name,
                        'short_code' => $child->short_code,
                    ])->values(),
            ])->values();

        return $this->success($items, $this->realtimeMeta(300));
    }

    public function show(Request $request, int $variation, AccessContext $access)
    {
        if (! $this->canView($request)) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);
        $base = $this->baseVariationQuery(
            $access->businessId($request),
            $locationIds,
            $locationId,
            $access
        )->where('v.id', $variation);
        $row = DB::query()->fromSub($base, 'inventory')->first();
        if (empty($row)) {
            return $this->failure(__('Product not found.'), [], 404, 'not_found');
        }

        $locationStock = DB::table('variation_location_details as vld')
            ->join('business_locations as bl', 'bl.id', '=', 'vld.location_id')
            ->where('vld.variation_id', $variation)
            ->whereIn('vld.location_id', $locationIds ?: [0])
            ->when($locationId, fn ($query) => $query->where('vld.location_id', $locationId))
            ->orderBy('bl.name')
            ->get(['bl.id', 'bl.name', 'vld.qty_available'])
            ->map(fn ($stock) => [
                'location_id' => (int) $stock->id,
                'location_name' => $stock->name,
                'stock' => round((float) $stock->qty_available, 4),
            ])->values();

        $sales = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->where('t.business_id', $access->businessId($request))
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('tsl.variation_id', $variation)
            ->where('t.transaction_date', '>=', now()->subDays(30)->startOfDay());
        $access->applyLocationScope($sales, 't.location_id', $locationIds, $locationId);
        $sales30Days = $sales->selectRaw(
            'COALESCE(SUM(tsl.quantity - tsl.quantity_returned), 0) as quantity, COALESCE(SUM((tsl.quantity - tsl.quantity_returned) * tsl.unit_price_inc_tax), 0) as total'
        )->first();

        return $this->success(array_merge($this->presentProduct($row), [
            'description' => $row->description,
            'category' => [
                'id' => $row->category_id ? (int) $row->category_id : null,
                'name' => $row->category_name,
            ],
            'sub_category' => [
                'id' => $row->sub_category_id ? (int) $row->sub_category_id : null,
                'name' => $row->sub_category_name,
            ],
            'brand' => $row->brand_name,
            'stock_by_location' => $locationStock,
            'sales_last_30_days' => [
                'quantity' => round((float) ($sales30Days->quantity ?? 0), 4),
                'total' => round((float) ($sales30Days->total ?? 0), 4),
            ],
        ]), $this->realtimeMeta(20));
    }

    protected function productList(ListRequest $request, AccessContext $access, bool $lowStockOnly)
    {
        $businessId = $access->businessId($request);
        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);
        $base = $this->baseVariationQuery($businessId, $locationIds, $locationId, $access);

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $base->where(function ($nested) use ($search) {
                $nested->where('p.name', 'like', $search)
                    ->orWhere('p.sku', 'like', $search)
                    ->orWhere('v.sub_sku', 'like', $search)
                    ->orWhere('pv.name', 'like', $search)
                    ->orWhere('v.name', 'like', $search);
            });
        }
        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $base->where(function ($category) use ($categoryId) {
                $category->where('p.category_id', $categoryId)
                    ->orWhere('p.sub_category_id', $categoryId);
            });
        }

        $query = DB::query()->fromSub($base, 'inventory');
        if ($lowStockOnly) {
            $query->where('enable_stock', 1)->whereColumn('stock', '<=', 'alert_quantity');
        }
        if ($request->filled('stock_status')) {
            $status = $request->input('stock_status');
            match ($status) {
                'in_stock' => $query->where('enable_stock', 1)->whereColumn('stock', '>', 'alert_quantity'),
                'low_stock' => $query->where('enable_stock', 1)->where('stock', '>', 0)->whereColumn('stock', '<=', 'alert_quantity'),
                'out_of_stock' => $query->where('enable_stock', 1)->where('stock', '<=', 0),
                'not_managed' => $query->where('enable_stock', 0),
                default => null,
            };
        }

        $paginator = $query
            ->orderBy($lowStockOnly ? 'stock' : 'product_name')
            ->paginate((int) $request->input('per_page', 20));

        $paginator->setCollection(
            $paginator->getCollection()->map(fn ($row) => $this->presentProduct($row))
        );

        return $this->success($paginator->items(), array_merge($this->realtimeMeta(20), [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'location_id' => $locationId,
        ]));
    }

    protected function baseVariationQuery(
        int $businessId,
        array $permittedLocationIds,
        ?int $locationId,
        AccessContext $access
    ) {
        $query = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($locationId) {
                $join->on('vld.variation_id', '=', 'v.id');
                if (! empty($locationId)) {
                    $join->where('vld.location_id', '=', $locationId);
                }
            })
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('categories as sc', 'sc.id', '=', 'p.sub_category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->where('p.business_id', $businessId)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at');

        if (empty($locationId)) {
            $access->applyLocationScope($query, 'vld.location_id', $permittedLocationIds);
        }

        return $query
            ->selectRaw(
                'p.id as product_id, v.id as variation_id, p.name as product_name, p.description, p.category_id, c.name as category_name, p.sub_category_id, sc.name as sub_category_name, b.name as brand_name, CASE WHEN pv.is_dummy = 1 THEN NULL ELSE CONCAT(COALESCE(pv.name, ""), " ", COALESCE(v.name, "")) END as variation_name, COALESCE(v.sub_sku, p.sku) as sku, u.short_name as unit, p.enable_stock, p.alert_quantity, v.dpp_inc_tax as purchase_price, v.sell_price_inc_tax as sell_price, p.image, COALESCE(SUM(vld.qty_available), 0) as stock'
            )
            ->groupBy(
                'p.id',
                'v.id',
                'p.name',
                'p.description',
                'p.category_id',
                'c.name',
                'p.sub_category_id',
                'sc.name',
                'b.name',
                'pv.is_dummy',
                'pv.name',
                'v.name',
                'v.sub_sku',
                'p.sku',
                'u.short_name',
                'p.enable_stock',
                'p.alert_quantity',
                'v.dpp_inc_tax',
                'v.sell_price_inc_tax',
                'p.image'
            );
    }

    protected function presentProduct($row): array
    {
        return [
            'product_id' => (int) $row->product_id,
            'variation_id' => (int) $row->variation_id,
            'product_name' => $row->product_name,
            'variation_name' => $row->variation_name,
            'sku' => $row->sku,
            'unit' => $row->unit,
            'category_id' => $row->category_id ? (int) $row->category_id : null,
            'category_name' => $row->category_name,
            'sub_category_id' => $row->sub_category_id ? (int) $row->sub_category_id : null,
            'sub_category_name' => $row->sub_category_name,
            'brand_name' => $row->brand_name,
            'enable_stock' => (bool) $row->enable_stock,
            'stock' => round((float) $row->stock, 4),
            'alert_quantity' => round((float) $row->alert_quantity, 4),
            'purchase_price' => round((float) $row->purchase_price, 4),
            'sell_price' => round((float) $row->sell_price, 4),
            'stock_status' => $this->stockStatus($row),
            'image_url' => ! empty($row->image)
                ? asset('uploads/img/'.rawurlencode($row->image))
                : asset('img/default.png'),
        ];
    }

    protected function stockStatus($row): string
    {
        if (! $row->enable_stock) {
            return 'not_managed';
        }
        if ((float) $row->stock <= 0) {
            return 'out_of_stock';
        }
        if ((float) $row->stock <= (float) $row->alert_quantity) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    protected function canView(Request $request): bool
    {
        return $request->user()->can('stock_report.view')
            || $request->user()->can('product.view')
            || $request->user()->can('dashboard.data');
    }
}
