<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\StorefrontService;

class CartController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CheckoutService $checkoutService
    ) {
    }

    public function show(string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $cart = $this->checkoutService->getCartDetails($store);
        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::cart.show', compact('store', 'settings', 'cart', 'cartCount'));
    }

    public function add(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $validated = $request->validate([
            'variation_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->checkoutService->addToCart($store, (int) $validated['variation_id'], (int) ($validated['quantity'] ?? 1));

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => __('ecommerce::lang.product_added_to_cart'),
        ]);
    }

    public function update(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $this->checkoutService->updateCartQuantities($store, $request->input('quantities', []));

        return redirect()->route('ecommerce.cart.show', $store->slug)->with('status', [
            'success' => 1,
            'msg' => __('ecommerce::lang.cart_updated_successfully'),
        ]);
    }

    public function remove(string $store_slug, int $variation_id)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $this->checkoutService->removeFromCart($store, $variation_id);

        return redirect()->route('ecommerce.cart.show', $store->slug)->with('status', [
            'success' => 1,
            'msg' => __('ecommerce::lang.item_removed_from_cart'),
        ]);
    }

    public function buyNow(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $validated = $request->validate([
            'variation_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->checkoutService->setBuyNowItem($store, (int) $validated['variation_id'], (int) ($validated['quantity'] ?? 1));

        return redirect()->route('ecommerce.checkout.show', ['store_slug' => $store->slug, 'mode' => 'buy_now']);
    }

    public function buyNowRedirect(string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $details = $this->checkoutService->getCheckoutCartDetails($store, 'buy_now');
        if (empty($details['items'])) {
            return redirect()->route('ecommerce.storefront.products', $store->slug);
        }

        return redirect()->route('ecommerce.checkout.show', ['store_slug' => $store->slug, 'mode' => 'buy_now']);
    }
}
