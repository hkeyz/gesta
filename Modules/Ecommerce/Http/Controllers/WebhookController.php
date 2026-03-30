<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ecommerce\Entities\EcomCheckoutSession;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\StorefrontService;
use Stripe\Event;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService,
        protected CheckoutService $checkoutService
    ) {
    }

    public function stripe(Request $request, string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug, false);
        $settings = $this->storefrontService->getStoreSettings($store);
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            if (! empty($settings['stripe_webhook_secret'])) {
                $event = Webhook::constructEvent($payload, $signature, $settings['stripe_webhook_secret']);
            } else {
                $event = Event::constructFrom(json_decode($payload, true));
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $object = $event->data->object;
        $checkout = EcomCheckoutSession::where('store_id', $store->id)
            ->where(function ($query) use ($object) {
                $query->where('stripe_session_id', $object->id ?? null)
                    ->orWhere('token', $object->metadata->ecom_checkout_token ?? null);
            })
            ->first();

        if (empty($checkout)) {
            return response()->json(['message' => 'Checkout session not found.'], 404);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $this->checkoutService->finalizeStripeCheckout($checkout, $object);
        }

        if (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $checkout->status = 'failed';
            $checkout->failure_reason = 'Stripe checkout expired or failed.';
            $checkout->save();
        }

        return response()->json(['received' => true]);
    }
}
