<?php

namespace Modules\Ecommerce\Services;

use App\Contact;
use App\Variation;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Entities\EcomCheckoutSession;
use Modules\Ecommerce\Entities\EcomCustomer;
use Modules\Ecommerce\Entities\EcomCustomerBusinessContact;
use Modules\Ecommerce\Entities\EcomProductListing;
use Modules\Ecommerce\Entities\EcomStore;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class CheckoutService
{
    public function __construct(
        protected ProductUtil $productUtil,
        protected TransactionUtil $transactionUtil,
        protected ContactUtil $contactUtil,
        protected ModuleUtil $moduleUtil
    ) {
    }

    public function getCartCount(EcomStore $store): int
    {
        return array_sum($this->getRawCart($store));
    }

    public function addToCart(EcomStore $store, int $variationId, int $quantity = 1): void
    {
        $this->assertVariationAvailable($store, $variationId, $quantity);
        $cart = $this->getRawCart($store);
        $cart[$variationId] = ($cart[$variationId] ?? 0) + $quantity;
        session([$this->cartSessionKey($store) => $cart]);
    }

    public function updateCartQuantities(EcomStore $store, array $quantities): void
    {
        $cart = [];
        foreach ($quantities as $variationId => $quantity) {
            $variationId = (int) $variationId;
            $quantity = (int) $quantity;
            if ($variationId > 0 && $quantity > 0) {
                $this->assertVariationAvailable($store, $variationId, $quantity);
                $cart[$variationId] = $quantity;
            }
        }

        session([$this->cartSessionKey($store) => $cart]);
    }

    public function removeFromCart(EcomStore $store, int $variationId): void
    {
        $cart = $this->getRawCart($store);
        unset($cart[$variationId]);
        session([$this->cartSessionKey($store) => $cart]);
    }

    public function setBuyNowItem(EcomStore $store, int $variationId, int $quantity = 1): void
    {
        $this->assertVariationAvailable($store, $variationId, $quantity);
        session([$this->buyNowSessionKey($store) => [$variationId => $quantity]]);
    }

    public function clearCart(EcomStore $store): void
    {
        session()->forget($this->cartSessionKey($store));
    }

    public function clearBuyNow(EcomStore $store): void
    {
        session()->forget($this->buyNowSessionKey($store));
    }

    public function getCartDetails(EcomStore $store): array
    {
        return $this->hydrateCartItems($store, $this->getRawCart($store));
    }

    public function getCheckoutCartDetails(EcomStore $store, string $mode = 'cart'): array
    {
        $items = $mode === 'buy_now'
            ? $this->getRawBuyNow($store)
            : $this->getRawCart($store);

        $details = $this->hydrateCartItems($store, $items);
        $details['mode'] = $mode;

        return $details;
    }

    public function resolveAuthenticatedCustomer(): ?EcomCustomer
    {
        return Auth::guard('ecom_customer')->user();
    }

    public function getCustomerPrefill(EcomStore $store, ?EcomCustomer $customer): array
    {
        if (empty($customer)) {
            return [];
        }

        $mapping = EcomCustomerBusinessContact::with('contact')
            ->where('ecom_customer_id', $customer->id)
            ->where('business_id', $store->business_id)
            ->first();

        $contact = optional($mapping)->contact;

        return [
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone ?? optional($contact)->mobile,
            'address_line_1' => optional($contact)->address_line_1,
            'address_line_2' => optional($contact)->address_line_2,
            'city' => optional($contact)->city,
            'state' => optional($contact)->state,
            'country' => optional($contact)->country,
            'zip_code' => optional($contact)->zip_code,
        ];
    }

    public function findOrCreateCustomerAccount(array $input): ?EcomCustomer
    {
        $current = $this->resolveAuthenticatedCustomer();
        if (! empty($current)) {
            return $current;
        }

        if (empty($input['create_account'])) {
            return null;
        }

        $existing = EcomCustomer::where('email', $input['email'])->first();
        if (! empty($existing)) {
            if (empty($input['password']) || ! Hash::check($input['password'], $existing->password)) {
                throw ValidationException::withMessages([
                    'email' => __('ecommerce::lang.an_account_exists_sign_in'),
                ]);
            }

            return $existing;
        }

        return EcomCustomer::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'] ?? null,
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null,
            'password' => Hash::make($input['password']),
        ]);
    }

    public function buildCheckoutContext(EcomStore $store, array $input, string $mode = 'cart', ?EcomCustomer $customer = null): array
    {
        $details = $this->getCheckoutCartDetails($store, $mode);
        if (empty($details['items'])) {
            throw ValidationException::withMessages([
                'cart' => __('ecommerce::lang.empty_cart_status'),
            ]);
        }

        if (! $details['can_checkout']) {
            throw ValidationException::withMessages([
                'cart' => __('ecommerce::lang.cart_items_unavailable'),
            ]);
        }

        $storeSettings = app(StorefrontService::class)->getStoreSettings($store);
        $shippingMethod = $input['shipping_method'] ?? 'pickup';
        $shippingCharge = $shippingMethod === 'delivery' ? (float) ($storeSettings['flat_shipping_rate'] ?? 0) : 0;
        $shippingLabel = $shippingMethod === 'delivery'
            ? ($storeSettings['flat_shipping_label'] ?? __('ecommerce::lang.standard_delivery'))
            : __('ecommerce::lang.pick_up_in_store');

        $firstName = $input['first_name'] ?? optional($customer)->first_name;
        $lastName = $input['last_name'] ?? optional($customer)->last_name;
        $email = $input['email'] ?? optional($customer)->email;
        $phone = $input['phone'] ?? optional($customer)->phone;

        $fullAddress = collect([
            $input['address_line_1'] ?? null,
            $input['address_line_2'] ?? null,
            $input['city'] ?? null,
            $input['state'] ?? null,
            $input['country'] ?? null,
            $input['zip_code'] ?? null,
        ])->filter()->implode(', ');

        return [
            'mode' => $mode,
            'customer' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ],
            'shipping' => [
                'method' => $shippingMethod,
                'label' => $shippingLabel,
                'full_address' => $fullAddress,
            ],
            'order_addresses' => [
                'shipping_method' => $shippingMethod,
                'shipping_label' => $shippingLabel,
                'billing' => [
                    'name' => trim($firstName.' '.$lastName),
                    'email' => $email,
                    'phone' => $phone,
                ],
                'shipping' => [
                    'address_line_1' => $input['address_line_1'] ?? null,
                    'address_line_2' => $input['address_line_2'] ?? null,
                    'city' => $input['city'] ?? null,
                    'state' => $input['state'] ?? null,
                    'country' => $input['country'] ?? null,
                    'zip_code' => $input['zip_code'] ?? null,
                ],
            ],
            'notes' => $input['notes'] ?? null,
            'payment_method' => $input['payment_method'],
            'products' => array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'],
                    'product_type' => $item['product_type'],
                    'product_unit_id' => $item['unit_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'item_tax' => $item['item_tax'],
                    'tax_id' => $item['tax_id'],
                    'unit_price_inc_tax' => $item['unit_price_inc_tax'],
                    'enable_stock' => $item['enable_stock'],
                    'product_name' => $item['product_name'],
                    'variation_name' => $item['variation_name'],
                ];
            }, $details['items']),
            'totals' => [
                'subtotal_ex_tax' => $details['totals']['subtotal_ex_tax'],
                'tax_total' => $details['totals']['tax_total'],
                'subtotal' => $details['totals']['subtotal'],
                'shipping_charge' => $shippingCharge,
                'grand_total' => $details['totals']['subtotal'] + $shippingCharge,
            ],
            'snapshot' => $details['items'],
        ];
    }

    public function getOrCreateBusinessContact(EcomStore $store, array $context, ?EcomCustomer $customer = null): Contact
    {
        if (! empty($customer)) {
            $mapping = EcomCustomerBusinessContact::with('contact')
                ->where('ecom_customer_id', $customer->id)
                ->where('business_id', $store->business_id)
                ->first();

            if (! empty($mapping?->contact)) {
                return $mapping->contact;
            }
        }

        $email = $context['customer']['email'] ?? null;
        $phone = $context['customer']['phone'] ?? null;
        $contact = null;

        if (! empty($email) || ! empty($phone)) {
            $query = Contact::where('business_id', $store->business_id)
                ->whereIn('type', ['customer', 'both'])
                ->where(function ($q) use ($email, $phone) {
                    if (! empty($email)) {
                        $q->where('email', $email);
                    }
                    if (! empty($phone)) {
                        if (! empty($email)) {
                            $q->orWhere('mobile', $phone);
                        } else {
                            $q->where('mobile', $phone);
                        }
                    }
                });

            $contact = $query->first();
        }

        if (empty($contact)) {
            $contact = $this->contactUtil->createNewContact([
                'type' => 'customer',
                'business_id' => $store->business_id,
                'name' => trim(($context['customer']['first_name'] ?? '').' '.($context['customer']['last_name'] ?? '')),
                'first_name' => $context['customer']['first_name'] ?? null,
                'last_name' => $context['customer']['last_name'] ?? null,
                'mobile' => $phone,
                'email' => $email,
                'address_line_1' => $context['order_addresses']['shipping']['address_line_1'] ?? null,
                'address_line_2' => $context['order_addresses']['shipping']['address_line_2'] ?? null,
                'city' => $context['order_addresses']['shipping']['city'] ?? null,
                'state' => $context['order_addresses']['shipping']['state'] ?? null,
                'country' => $context['order_addresses']['shipping']['country'] ?? null,
                'zip_code' => $context['order_addresses']['shipping']['zip_code'] ?? null,
                'contact_status' => 'active',
                'created_by' => $store->business->owner_id,
            ])['data'];
        }

        if (! empty($customer)) {
            EcomCustomerBusinessContact::updateOrCreate(
                [
                    'ecom_customer_id' => $customer->id,
                    'business_id' => $store->business_id,
                ],
                [
                    'contact_id' => $contact->id,
                ]
            );
        }

        return $contact;
    }

    public function placeManualOrder(EcomStore $store, array $context, ?EcomCustomer $customer = null): EcomCheckoutSession
    {
        return DB::transaction(function () use ($store, $context, $customer) {
            $store->loadMissing('business');
            $contact = $this->getOrCreateBusinessContact($store, $context, $customer);
            $transaction = $this->createTransaction($store, $context, $contact, 'sales_order', 'ordered');
            $transaction->payment_status = 'due';
            $transaction->save();

            $checkout = EcomCheckoutSession::create([
                'store_id' => $store->id,
                'business_id' => $store->business_id,
                'ecom_customer_id' => $customer?->id,
                'contact_id' => $contact->id,
                'transaction_id' => $transaction->id,
                'token' => (string) Str::uuid(),
                'mode' => $context['mode'],
                'status' => 'completed',
                'amount' => $context['totals']['grand_total'],
                'currency_code' => optional($store->business->currency)->code,
                'cart_snapshot' => $context['snapshot'],
                'checkout_context' => $context,
                'completed_at' => Carbon::now(),
            ]);

            return $checkout;
        });
    }

    public function createStripeCheckout(EcomStore $store, array $context, ?EcomCustomer $customer = null): EcomCheckoutSession
    {
        $store->loadMissing('business.currency');
        $contact = $this->getOrCreateBusinessContact($store, $context, $customer);
        $posSettings = ! empty($store->business->pos_settings) ? json_decode($store->business->pos_settings, true) : [];
        $secretKey = $posSettings['stripe_secret_key'] ?? null;

        if (empty($secretKey)) {
            throw ValidationException::withMessages([
                'payment_method' => __('ecommerce::lang.stripe_not_configured'),
            ]);
        }

        $checkout = EcomCheckoutSession::create([
            'store_id' => $store->id,
            'business_id' => $store->business_id,
            'ecom_customer_id' => $customer?->id,
            'contact_id' => $contact->id,
            'token' => (string) Str::uuid(),
            'mode' => $context['mode'],
            'status' => 'pending',
            'amount' => $context['totals']['grand_total'],
            'currency_code' => optional($store->business->currency)->code,
            'cart_snapshot' => $context['snapshot'],
            'checkout_context' => $context,
        ]);

        Stripe::setApiKey($secretKey);

        $successUrl = route('ecommerce.checkout.success', [$store->slug, $checkout->token]).'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('ecommerce.checkout.cancel', [$store->slug, $checkout->token]);

        $lineItems = [];
        foreach ($context['products'] as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($checkout->currency_code ?: 'usd'),
                    'product_data' => [
                        'name' => trim($item['product_name'].' '.$item['variation_name']),
                    ],
                    'unit_amount' => (int) round($item['unit_price_inc_tax'] * 100),
                ],
                'quantity' => (int) $item['quantity'],
            ];
        }

        if ($context['totals']['shipping_charge'] > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($checkout->currency_code ?: 'usd'),
                    'product_data' => [
                        'name' => $context['shipping']['label'],
                    ],
                    'unit_amount' => (int) round($context['totals']['shipping_charge'] * 100),
                ],
                'quantity' => 1,
            ];
        }

        $stripeSession = StripeCheckoutSession::create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $context['customer']['email'] ?? null,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'metadata' => [
                'ecom_checkout_token' => $checkout->token,
                'store_slug' => $store->slug,
            ],
        ]);

        $checkout->stripe_session_id = $stripeSession->id;
        $checkout->save();
        $checkout->setAttribute('redirect_url', $stripeSession->url ?? null);

        return $checkout;
    }

    public function finalizeStripeCheckout(EcomCheckoutSession $checkout, $stripeSession = null): ?\App\Transaction
    {
        if (! empty($checkout->transaction_id) && $checkout->status === 'completed') {
            return $checkout->transaction;
        }

        $checkout->loadMissing('store.business.currency', 'contact', 'customer');
        $store = $checkout->store;
        $context = $checkout->checkout_context ?? [];

        $posSettings = ! empty($store->business->pos_settings) ? json_decode($store->business->pos_settings, true) : [];
        $secretKey = $posSettings['stripe_secret_key'] ?? null;
        if (empty($stripeSession) && ! empty($secretKey) && ! empty($checkout->stripe_session_id)) {
            Stripe::setApiKey($secretKey);
            $stripeSession = StripeCheckoutSession::retrieve($checkout->stripe_session_id);
        }

        if (empty($stripeSession) || ($stripeSession->payment_status ?? null) !== 'paid') {
            return null;
        }

        foreach (($context['products'] ?? []) as $item) {
            if (! empty($item['enable_stock'])) {
                $currentStock = $this->productUtil->getCurrentStock($item['variation_id'], $store->location_id);
                if ($currentStock < $item['quantity']) {
                    $checkout->status = 'failed_stock';
                    $checkout->failure_reason = __('ecommerce::lang.insufficient_stock_payment_confirmation');
                    $checkout->save();

                    return null;
                }
            }
        }

        return DB::transaction(function () use ($checkout, $store, $context, $stripeSession) {
            $contact = $checkout->contact ?: $this->getOrCreateBusinessContact($store, $context, $checkout->customer);
            $transaction = $this->createTransaction($store, $context, $contact, 'sell', 'final');

            foreach ($context['products'] as $item) {
                if (! empty($item['enable_stock'])) {
                    $this->productUtil->decreaseProductQuantity(
                        $item['product_id'],
                        $item['variation_id'],
                        $store->location_id,
                        $item['quantity']
                    );
                }
            }

            $businessData = [
                'id' => $store->business_id,
                'accounting_method' => $store->business->accounting_method,
                'location_id' => $store->location_id,
            ];
            $this->transactionUtil->mapPurchaseSell($businessData, $transaction->sell_lines, 'purchase');

            $this->transactionUtil->createOrUpdatePaymentLines($transaction, [[
                'amount' => $context['totals']['grand_total'],
                'method' => 'card',
                'paid_on' => Carbon::now()->toDateTimeString(),
                'note' => __('ecommerce::lang.stripe_checkout_note', ['id' => $stripeSession->id]),
            ]], $store->business_id, $store->business->owner_id, false);

            $transaction->payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            $transaction->save();

            $checkout->transaction_id = $transaction->id;
            $checkout->status = 'completed';
            $checkout->paid_at = Carbon::now();
            $checkout->completed_at = Carbon::now();
            $checkout->stripe_payment_intent_id = $stripeSession->payment_intent ?? null;
            $checkout->save();

            $this->moduleUtil->getModuleData('after_sale_saved', [
                'transaction' => $transaction,
                'input' => [],
            ]);

            return $transaction;
        });
    }

    public function getAvailablePaymentMethods(EcomStore $store): array
    {
        $methods = [
            'cash' => __('ecommerce::lang.payment_method_cash'),
            'bank_transfer' => __('ecommerce::lang.payment_method_bank_transfer'),
        ];

        $posSettings = ! empty($store->business->pos_settings) ? json_decode($store->business->pos_settings, true) : [];
        if (! empty($posSettings['stripe_public_key']) && ! empty($posSettings['stripe_secret_key'])) {
            $methods['stripe'] = __('ecommerce::lang.payment_method_stripe');
        }

        return $methods;
    }

    private function getRawCart(EcomStore $store): array
    {
        return array_map('intval', session($this->cartSessionKey($store), []));
    }

    private function getRawBuyNow(EcomStore $store): array
    {
        return array_map('intval', session($this->buyNowSessionKey($store), []));
    }

    private function cartSessionKey(EcomStore $store): string
    {
        return 'ecommerce.cart.'.$store->slug;
    }

    private function buyNowSessionKey(EcomStore $store): string
    {
        return 'ecommerce.buy_now.'.$store->slug;
    }

    private function hydrateCartItems(EcomStore $store, array $rawItems): array
    {
        if (empty($rawItems)) {
            return [
                'items' => [],
                'totals' => [
                    'subtotal_ex_tax' => 0,
                    'tax_total' => 0,
                    'subtotal' => 0,
                ],
                'can_checkout' => false,
            ];
        }

        $variationIds = array_map('intval', array_keys($rawItems));
        $variations = Variation::query()
            ->with(['product.brand', 'product.category', 'product.sub_category', 'product.product_tax', 'product_variation'])
            ->leftJoin('variation_location_details as vld', function ($join) use ($store) {
                $join->on('vld.variation_id', '=', 'variations.id');
                if (! empty($store->location_id)) {
                    $join->where('vld.location_id', '=', $store->location_id);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->whereIn('variations.id', $variationIds)
            ->select('variations.*', DB::raw('COALESCE(vld.qty_available, 0) as stock_qty'))
            ->get();

        $listings = EcomProductListing::where('store_id', $store->id)
            ->whereIn('product_id', $variations->pluck('product_id')->all())
            ->published()
            ->get()
            ->keyBy('product_id');

        $items = [];
        $subtotalExTax = 0;
        $taxTotal = 0;
        $subtotal = 0;
        $canCheckout = true;

        foreach ($variations as $variation) {
            $product = $variation->product;
            $listing = $listings->get($variation->product_id);

            if (empty($product) || empty($listing) || $product->business_id != $store->business_id || $product->is_inactive || $product->not_for_selling) {
                continue;
            }

            $quantity = max(1, (int) ($rawItems[$variation->id] ?? 1));
            $availableQty = ! empty($product->enable_stock) ? (float) $variation->stock_qty : null;
            if (! empty($product->enable_stock) && $availableQty < $quantity) {
                $canCheckout = false;
            }

            $unitPrice = (float) $variation->default_sell_price;
            $unitPriceIncTax = (float) $variation->sell_price_inc_tax;
            $itemTax = max(0, $unitPriceIncTax - $unitPrice);
            $lineSubtotalExTax = $unitPrice * $quantity;
            $lineTax = $itemTax * $quantity;
            $lineTotal = $unitPriceIncTax * $quantity;

            $variationName = $product->type === 'variable'
                ? trim(optional($variation->product_variation)->name.' '.$variation->name)
                : __('ecommerce::lang.default_variation');

            $items[] = [
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'product_name' => $product->name,
                'variation_name' => $variationName,
                'product_type' => $product->type,
                'unit_id' => $product->unit_id,
                'tax_id' => $product->tax,
                'quantity' => $quantity,
                'available_qty' => $availableQty,
                'enable_stock' => (int) $product->enable_stock,
                'unit_price' => round($unitPrice, 4),
                'item_tax' => round($itemTax, 4),
                'unit_price_inc_tax' => round($unitPriceIncTax, 4),
                'line_total' => round($lineTotal, 4),
                'image_url' => $product->image_url,
                'product_url' => route('ecommerce.storefront.product', [$store->slug, $listing->slug]),
                'listing_slug' => $listing->slug,
                'sku' => $variation->sub_sku,
            ];

            $subtotalExTax += $lineSubtotalExTax;
            $taxTotal += $lineTax;
            $subtotal += $lineTotal;
        }

        return [
            'items' => $items,
            'totals' => [
                'subtotal_ex_tax' => round($subtotalExTax, 4),
                'tax_total' => round($taxTotal, 4),
                'subtotal' => round($subtotal, 4),
            ],
            'can_checkout' => $canCheckout && ! empty($items),
        ];
    }

    private function assertVariationAvailable(EcomStore $store, int $variationId, int $quantity): void
    {
        $variation = Variation::with('product')->findOrFail($variationId);
        $listingExists = EcomProductListing::where('store_id', $store->id)
            ->where('product_id', $variation->product_id)
            ->published()
            ->exists();

        if (! $listingExists || empty($variation->product) || $variation->product->business_id != $store->business_id) {
            throw ValidationException::withMessages([
                'product' => __('ecommerce::lang.product_not_available'),
            ]);
        }

        if (! empty($variation->product->enable_stock)) {
            $stock = $this->productUtil->getCurrentStock($variationId, $store->location_id);
            if ($stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => __('ecommerce::lang.quantity_not_available'),
                ]);
            }
        }
    }

    private function createTransaction(EcomStore $store, array $context, Contact $contact, string $type, string $status): \App\Transaction
    {
        $store->loadMissing('business');

        $payload = [
            'type' => $type,
            'location_id' => $store->location_id,
            'contact_id' => $contact->id,
            'final_total' => $context['totals']['grand_total'],
            'status' => $status,
            'transaction_date' => Carbon::now()->toDateTimeString(),
            'customer_group_id' => $contact->customer_group_id,
            'tax_rate_id' => null,
            'sale_note' => $context['notes'] ?? null,
            'commission_agent' => null,
            'order_addresses' => json_encode($context['order_addresses']),
            'products' => $context['products'],
            'is_created_from_api' => 1,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'shipping_charges' => $context['totals']['shipping_charge'],
            'shipping_details' => $context['shipping']['label'] ?? null,
            'shipping_address' => $context['shipping']['full_address'] ?? null,
            'source' => 'ecommerce',
        ];

        $invoiceTotal = [
            'total_before_tax' => $context['totals']['subtotal_ex_tax'] + $context['totals']['shipping_charge'],
            'tax' => $context['totals']['tax_total'],
        ];

        $transaction = $this->transactionUtil->createSellTransaction(
            $store->business_id,
            $payload,
            $invoiceTotal,
            $store->business->owner_id,
            false
        );

        $this->transactionUtil->createOrUpdateSellLines(
            $transaction,
            $context['products'],
            $store->location_id,
            false,
            null,
            [],
            false
        );

        return $transaction->fresh(['sell_lines']);
    }
}

