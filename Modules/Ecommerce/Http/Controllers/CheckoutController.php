<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Entities\EcomCheckoutSession;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\StorefrontService;

class CheckoutController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CheckoutService $checkoutService
    ) {
    }

    public function show(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $mode = $request->input('mode', 'cart');
        $checkout = $this->checkoutService->getCheckoutCartDetails($store, $mode);
        $customer = $this->checkoutService->resolveAuthenticatedCustomer();
        $prefill = $this->checkoutService->getCustomerPrefill($store, $customer);
        $paymentMethods = $this->checkoutService->getAvailablePaymentMethods($store);
        $cartCount = $this->checkoutService->getCartCount($store);

        if (empty($checkout['items'])) {
            return redirect()->route('ecommerce.cart.show', $store->slug)->with('status', [
                'success' => 0,
                'msg' => 'Your cart is empty.',
            ]);
        }

        return view('ecommerce::checkout.show', compact('store', 'settings', 'checkout', 'paymentMethods', 'customer', 'prefill', 'mode', 'cartCount'));
    }

    public function store(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $paymentMethods = $this->checkoutService->getAvailablePaymentMethods($store);
        $customer = $this->checkoutService->resolveAuthenticatedCustomer();
        $mode = $request->input('mode', 'cart');

        $validated = $request->validate([
            'mode' => ['nullable', 'in:cart,buy_now'],
            'shipping_method' => ['required', 'in:pickup,delivery'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys($paymentMethods))],
            'first_name' => [empty($customer) ? 'required' : 'nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => [empty($customer) ? 'required' : 'nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:191', 'required_if:shipping_method,delivery'],
            'address_line_2' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:120', 'required_if:shipping_method,delivery'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120', 'required_if:shipping_method,delivery'],
            'zip_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (empty($customer) && $request->boolean('create_account') && empty($validated['password'])) {
            return redirect()->back()->withErrors([
                'password' => 'A password is required to create an account.',
            ])->withInput();
        }

        $customerAccount = $this->checkoutService->findOrCreateCustomerAccount(array_merge($validated, [
            'create_account' => $request->boolean('create_account'),
        ]));

        if (! empty($customerAccount) && ! Auth::guard('ecom_customer')->check()) {
            Auth::guard('ecom_customer')->login($customerAccount, true);
        }

        $context = $this->checkoutService->buildCheckoutContext(
            $store,
            array_merge($validated, ['payment_method' => $validated['payment_method']]),
            $mode,
            $customerAccount
        );

        if ($validated['payment_method'] === 'stripe') {
            $checkout = $this->checkoutService->createStripeCheckout($store, $context, $customerAccount);

            return redirect()->away($checkout->redirect_url);
        }

        $checkout = $this->checkoutService->placeManualOrder($store, $context, $customerAccount);
        $this->clearCheckoutMode($store, $mode);

        return redirect()->route('ecommerce.checkout.success', [$store->slug, $checkout->token]);
    }

    public function success(Request $request, string $store_slug, string $token)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $checkout = EcomCheckoutSession::with(['transaction.sell_lines.product', 'transaction.payment_lines'])
            ->where('store_id', $store->id)
            ->where('token', $token)
            ->firstOrFail();

        if ($checkout->status === 'pending') {
            $this->checkoutService->finalizeStripeCheckout($checkout);
            $checkout->refresh();
        }

        if ($checkout->status === 'completed') {
            $this->clearCheckoutMode($store, $checkout->mode ?: 'cart');
        }

        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::checkout.result', compact('store', 'settings', 'checkout', 'cartCount'));
    }

    public function cancel(string $store_slug, string $token)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);
        $checkout = EcomCheckoutSession::with('transaction')
            ->where('store_id', $store->id)
            ->where('token', $token)
            ->firstOrFail();

        if ($checkout->status === 'pending') {
            $checkout->status = 'cancelled';
            $checkout->failure_reason = 'Customer cancelled checkout.';
            $checkout->save();
        }

        $cartCount = $this->checkoutService->getCartCount($store);

        return view('ecommerce::checkout.result', compact('store', 'settings', 'checkout', 'cartCount'));
    }

    protected function clearCheckoutMode($store, string $mode): void
    {
        if ($mode === 'buy_now') {
            $this->checkoutService->clearBuyNow($store);
        } else {
            $this->checkoutService->clearCart($store);
        }
    }
}
