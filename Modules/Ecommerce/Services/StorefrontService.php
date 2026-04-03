<?php

namespace Modules\Ecommerce\Services;

use App\Category;
use App\Product;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ecommerce\Entities\EcomApiSetting;
use Modules\Ecommerce\Entities\EcomProductListing;
use Modules\Ecommerce\Entities\EcomStore;

class StorefrontService
{
    public function defaultStoreSettings(): array
    {
        return [
            'brand_name' => null,
            'tagline' => null,
            'accent_color' => '#1f6feb',
            'enable_pickup' => true,
            'enable_delivery' => true,
            'flat_shipping_label' => __('ecommerce::lang.standard_delivery'),
            'flat_shipping_rate' => 0,
            'stripe_webhook_secret' => null,
            'slides' => [],
        ];
    }

    public function getStoreBySlug(string $slug, bool $requireEnabled = true): EcomStore
    {
        $query = EcomStore::with(['business.currency', 'location'])
            ->where('slug', $slug);

        if ($requireEnabled) {
            $query->where('is_enabled', 1);
        }

        return $query->firstOrFail();
    }

    public function getStoreSettings(EcomStore $store): array
    {
        return array_merge($this->defaultStoreSettings(), $store->settings ?? []);
    }

    public function syncBusinessEcomSettings(EcomStore $store, ?EcomApiSetting $apiSetting = null): void
    {
        $store->loadMissing('business', 'apiSetting');

        $settings = $this->getStoreSettings($store);
        $apiSetting = $apiSetting ?: $store->apiSetting;

        $payload = [
            'enabled' => (bool) $store->is_enabled,
            'store_slug' => $store->slug,
            'store_url' => $store->public_url,
            'api_token' => optional($apiSetting)->api_token,
            'pickup_enabled' => (bool) ($settings['enable_pickup'] ?? true),
            'delivery_enabled' => (bool) ($settings['enable_delivery'] ?? true),
            'flat_shipping_rate' => (float) ($settings['flat_shipping_rate'] ?? 0),
            'flat_shipping_label' => $settings['flat_shipping_label'] ?? __('ecommerce::lang.standard_delivery'),
            'slides' => $settings['slides'] ?? [],
        ];

        $store->business->ecom_settings = json_encode($payload);
        $store->business->save();
    }

    public function ensureCategorySlugs(?int $businessId = null): void
    {
        $query = Category::query()
            ->where('category_type', 'product')
            ->where(function ($q) {
                $q->whereNull('slug')->orWhere('slug', '');
            });

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $query->orderBy('id')->get()->each(function (Category $category) {
            $category->slug = $this->uniqueSlug($category->name, function ($slug) use ($category) {
                return Category::where('business_id', $category->business_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $category->id)
                    ->exists();
            }, 'category');
            $category->save();
        });
    }

    public function productFormData(): array
    {
        $businessId = session()->get('user.business_id');
        $store = null;
        $listing = null;

        if (! empty($businessId)) {
            $store = EcomStore::where('business_id', $businessId)->first();

            $productId = request()->route('product');
            if (! empty($productId) && ! empty($store)) {
                $listing = EcomProductListing::where('store_id', $store->id)
                    ->where('product_id', $productId)
                    ->first();
            }
        }

        return [
            'store' => $store,
            'listing' => $listing,
        ];
    }

    public function upsertListingForProduct(Product $product, Request $request): void
    {
        if (! $request->filled('ecom_listing_form_present')) {
            return;
        }

        $store = EcomStore::where('business_id', $product->business_id)->first();
        if (empty($store)) {
            return;
        }

        $listing = EcomProductListing::firstOrNew([
            'store_id' => $store->id,
            'product_id' => $product->id,
        ]);

        $requestedSlug = $request->input('ecom_slug');
        $canPublish = ! $product->not_for_selling
            && (int) $product->is_inactive === 0
            && in_array($product->type, ['single', 'variable'], true);

        $listing->is_published = $canPublish && $request->boolean('ecom_publish_online');
        $listing->excerpt = $request->input('ecom_excerpt');
        $listing->meta_title = $request->input('ecom_meta_title');
        $listing->meta_description = $request->input('ecom_meta_description');
        $listing->slug = $this->generateListingSlug($store->id, $product->name, $requestedSlug, $listing->id ?: null);
        $listing->save();
    }

    public function generateListingSlug(int $storeId, string $productName, ?string $requestedSlug = null, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug($requestedSlug ?: $productName, function ($slug) use ($storeId, $ignoreId) {
            return EcomProductListing::where('store_id', $storeId)
                ->where('slug', $slug)
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->exists();
        }, 'product');
    }

    public function navigationCategories(EcomStore $store)
    {
        return Category::query()
            ->join('products', function ($join) use ($store) {
                $join->on('products.category_id', '=', 'categories.id')
                    ->where('products.business_id', '=', $store->business_id)
                    ->where('products.is_inactive', '=', 0)
                    ->where('products.not_for_selling', '=', 0);
            })
            ->join('ecom_product_listings', function ($join) use ($store) {
                $join->on('ecom_product_listings.product_id', '=', 'products.id')
                    ->where('ecom_product_listings.store_id', '=', $store->id)
                    ->where('ecom_product_listings.is_published', '=', 1);
            })
            ->where('categories.business_id', $store->business_id)
            ->where('categories.category_type', 'product')
            ->where('categories.parent_id', 0)
            ->select('categories.*')
            ->distinct()
            ->orderBy('categories.name')
            ->get();
    }

    public function availableBrands(EcomStore $store)
    {
        return DB::table('ecom_product_listings')
            ->join('products', function ($join) use ($store) {
                $join->on('products.id', '=', 'ecom_product_listings.product_id')
                    ->where('products.business_id', '=', $store->business_id)
                    ->where('products.is_inactive', '=', 0)
                    ->where('products.not_for_selling', '=', 0);
            })
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->where('ecom_product_listings.store_id', $store->id)
            ->where('ecom_product_listings.is_published', 1)
            ->select('brands.id', 'brands.name')
            ->distinct()
            ->orderBy('brands.name')
            ->get();
    }

    public function findCategoryForStore(EcomStore $store, string $slug): Category
    {
        return Category::where('business_id', $store->business_id)
            ->where('category_type', 'product')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function catalogQuery(EcomStore $store, Request $request, ?Category $category = null)
    {
        $stockSubquery = Variation::query()
            ->leftJoin('variation_location_details as vld', function ($join) use ($store) {
                $join->on('vld.variation_id', '=', 'variations.id');
                if (! empty($store->location_id)) {
                    $join->where('vld.location_id', '=', $store->location_id);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->whereColumn('variations.product_id', 'products.id')
            ->selectRaw('COALESCE(SUM(vld.qty_available), 0)');

        $minPriceSubquery = Variation::query()
            ->whereColumn('variations.product_id', 'products.id')
            ->selectRaw('COALESCE(MIN(sell_price_inc_tax), 0)');

        $maxPriceSubquery = Variation::query()
            ->whereColumn('variations.product_id', 'products.id')
            ->selectRaw('COALESCE(MAX(sell_price_inc_tax), 0)');

        $query = EcomProductListing::query()
            ->select('ecom_product_listings.*')
            ->join('products', 'products.id', '=', 'ecom_product_listings.product_id')
            ->where('ecom_product_listings.store_id', $store->id)
            ->where('ecom_product_listings.is_published', 1)
            ->where('products.business_id', $store->business_id)
            ->where('products.is_inactive', 0)
            ->where('products.not_for_selling', 0)
            ->whereIn('products.type', ['single', 'variable'])
            ->with(['product.brand', 'product.category', 'product.sub_category', 'product.product_tax'])
            ->addSelect([
                'min_price' => $minPriceSubquery,
                'max_price' => $maxPriceSubquery,
                'total_stock' => $stockSubquery,
            ]);

        if (! empty($category)) {
            $query->where(function ($q) use ($category) {
                $q->where('products.category_id', $category->id)
                    ->orWhere('products.sub_category_id', $category->id);
            });
        }

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', '%'.$search.'%')
                    ->orWhere('products.product_description', 'like', '%'.$search.'%')
                    ->orWhere('ecom_product_listings.excerpt', 'like', '%'.$search.'%');
            });
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('products.brand_id', $brandId);
        }

        if ($request->filled('min_price')) {
            $query->whereRaw('('.$minPriceSubquery->toSql().') >= ?', array_merge($minPriceSubquery->getBindings(), [(float) $request->input('min_price')]));
        }

        if ($request->filled('max_price')) {
            $query->whereRaw('('.$maxPriceSubquery->toSql().') <= ?', array_merge($maxPriceSubquery->getBindings(), [(float) $request->input('max_price')]));
        }

        if ($request->boolean('in_stock')) {
            $query->where(function ($q) use ($stockSubquery) {
                $q->where('products.enable_stock', 0)
                    ->orWhereRaw('('.$stockSubquery->toSql().') > 0', $stockSubquery->getBindings());
            });
        }

        $sort = $request->input('sort', 'newest');
        if ($sort === 'name_asc') {
            $query->orderBy('products.name');
        } elseif ($sort === 'price_low') {
            $query->orderBy('min_price');
        } elseif ($sort === 'price_high') {
            $query->orderByDesc('max_price');
        } else {
            $query->orderByDesc('products.created_at');
        }

        return $query;
    }

    public function getPublishedProductBySlug(EcomStore $store, string $slug): EcomProductListing
    {
        return $this->catalogQuery($store, new Request())
            ->where('ecom_product_listings.slug', $slug)
            ->firstOrFail();
    }

    public function getProductVariations(Product $product, ?int $locationId)
    {
        return Variation::query()
            ->with('product_variation')
            ->leftJoin('variation_location_details as vld', function ($join) use ($locationId) {
                $join->on('vld.variation_id', '=', 'variations.id');
                if (! empty($locationId)) {
                    $join->where('vld.location_id', '=', $locationId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->where('variations.product_id', $product->id)
            ->select('variations.*', DB::raw('COALESCE(vld.qty_available, 0) as stock_qty'))
            ->orderBy('variations.id')
            ->get()
            ->map(function ($variation) use ($product) {
                $variationName = $product->type === 'variable'
                    ? trim(optional($variation->product_variation)->name.' '.$variation->name)
                    : __('ecommerce::lang.default_variation');
                $variation->option_name = $variationName;

                return $variation;
            });
    }

    private function uniqueSlug(string $value, callable $exists, string $fallback): string
    {
        $base = Str::slug($value ?: $fallback);
        $base = $base ?: $fallback;
        $slug = $base;
        $suffix = 2;

        while ($exists($slug)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
