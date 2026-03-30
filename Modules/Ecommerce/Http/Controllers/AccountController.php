<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Transaction;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Entities\EcomCustomerBusinessContact;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\StorefrontService;

class AccountController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CheckoutService $checkoutService
    ) {
    }

    public function orders(string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $customer = Auth::guard('ecom_customer')->user();
        $contactId = EcomCustomerBusinessContact::where('ecom_customer_id', $customer->id)
            ->where('business_id', $store->business_id)
            ->value('contact_id');

        $orders = Transaction::with('payment_lines')
            ->where('business_id', $store->business_id)
            ->where('source', 'ecommerce')
            ->when($contactId, function ($query) use ($contactId) {
                $query->where('contact_id', $contactId);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderByDesc('transaction_date')
            ->paginate(10);

        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::account.orders', compact('store', 'settings', 'orders', 'cartCount'));
    }

    public function showOrder(string $store_slug, int $transaction)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $customer = Auth::guard('ecom_customer')->user();
        $contactId = EcomCustomerBusinessContact::where('ecom_customer_id', $customer->id)
            ->where('business_id', $store->business_id)
            ->value('contact_id');

        $order = Transaction::with(['sell_lines.product', 'payment_lines'])
            ->where('business_id', $store->business_id)
            ->where('source', 'ecommerce')
            ->where('contact_id', $contactId)
            ->findOrFail($transaction);

        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::account.order_show', compact('store', 'settings', 'order', 'cartCount'));
    }
}
