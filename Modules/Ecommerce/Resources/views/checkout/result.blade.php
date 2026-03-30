@extends('ecommerce::layouts.storefront')
@section('title', 'Order status')

@section('storefront_content')
<section class="sf-section sf-panel">
    @if($checkout->status === 'completed')
        <h2 style="margin-top:0;">Order confirmed</h2>
        <p>Your order has been recorded successfully.</p>
        @if(!empty($checkout->transaction))
            <p><strong>Reference:</strong> {{ $checkout->transaction->invoice_no }}</p>
            <p><strong>Total:</strong> {{ number_format($checkout->transaction->final_total, 2) }}</p>
        @endif
        @if(auth('ecom_customer')->check() || !empty($checkout->ecom_customer_id))
            <a class="sf-button sf-button--accent" href="{{ route('ecommerce.account.orders', $store->slug) }}">View my orders</a>
        @else
            <a class="sf-button sf-button--accent" href="{{ route('ecommerce.storefront.products', $store->slug) }}">Continue shopping</a>
        @endif
    @elseif($checkout->status === 'pending')
        <h2 style="margin-top:0;">Payment pending</h2>
        <p>Your checkout is still waiting for payment confirmation.</p>
    @else
        <h2 style="margin-top:0;">Checkout not completed</h2>
        <p>{{ $checkout->failure_reason ?: 'This checkout could not be completed.' }}</p>
        <a class="sf-button" href="{{ route('ecommerce.checkout.show', $store->slug) }}">Try again</a>
    @endif
</section>
@endsection