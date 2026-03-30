<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\StorefrontService;

class StorefrontController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CheckoutService $checkoutService
    ) {
    }

    public function index(string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $featuredProducts = $this->storefrontService->catalogQuery($store, request())
            ->limit(8)
            ->get();
        $categories = $this->storefrontService->navigationCategories($store);
        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::storefront.index', compact('store', 'settings', 'featuredProducts', 'categories', 'cartCount'));
    }

    public function catalog(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $products = $this->storefrontService->catalogQuery($store, $request)
            ->paginate(12)
            ->withQueryString();
        $categories = $this->storefrontService->navigationCategories($store);
        $brands = $this->storefrontService->availableBrands($store);
        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::products.index', compact('store', 'settings', 'products', 'categories', 'brands', 'cartCount'));
    }

    public function category(Request $request, string $store_slug, string $category_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $currentCategory = $this->storefrontService->findCategoryForStore($store, $category_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $products = $this->storefrontService->catalogQuery($store, $request, $currentCategory)
            ->paginate(12)
            ->withQueryString();
        $categories = $this->storefrontService->navigationCategories($store);
        $brands = $this->storefrontService->availableBrands($store);
        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::products.index', compact('store', 'settings', 'products', 'categories', 'brands', 'cartCount', 'currentCategory'));
    }

    public function show(string $store_slug, string $product_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $listing = $this->storefrontService->getPublishedProductBySlug($store, $product_slug);
        $product = $listing->product;
        $variations = $this->storefrontService->getProductVariations($product, $store->location_id);
        $relatedProducts = $this->storefrontService->catalogQuery($store, new Request())
            ->where('ecom_product_listings.id', '!=', $listing->id)
            ->where(function ($query) use ($product) {
                $query->where('products.category_id', $product->category_id)
                    ->orWhere('products.sub_category_id', $product->sub_category_id);
            })
            ->limit(4)
            ->get();
        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::products.show', compact('store', 'settings', 'listing', 'product', 'variations', 'relatedProducts', 'cartCount'));
    }
}