@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.order_status_page'))

@section('storefront_content')
<section class="sf-section sf-panel">
    @if($checkout->status === 'completed')
        <h2 style="margin-top:0;">@lang('ecommerce::lang.order_confirmed')</h2>
        <p>@lang('ecommerce::lang.order_recorded_successfully')</p>
        @if(!empty($checkout->transaction))
            <p><strong>@lang('ecommerce::lang.order_reference'):</strong> {{ $checkout->transaction->invoice_no }}</p>
            <p><strong>@lang('ecommerce::lang.total'):</strong> {{ number_format($checkout->transaction->final_total, 2) }}</p>
        @endif
        @if(auth('ecom_customer')->check() || !empty($checkout->ecom_customer_id))
            <a class="sf-button sf-button--accent" href="{{ route('ecommerce.account.orders', $store->slug) }}">@lang('ecommerce::lang.my_orders')</a>
        @else
            <a class="sf-button sf-button--accent" href="{{ route('ecommerce.storefront.products', $store->slug) }}">@lang('ecommerce::lang.continue_shopping')</a>
        @endif
    @elseif($checkout->status === 'pending')
        <h2 style="margin-top:0;">@lang('ecommerce::lang.payment_pending')</h2>
        <p>@lang('ecommerce::lang.checkout_waiting_payment_confirmation')</p>
    @else
        <h2 style="margin-top:0;">@lang('ecommerce::lang.checkout_not_completed')</h2>
        <p>{{ $checkout->failure_reason ?: __('ecommerce::lang.this_checkout_could_not_be_completed') }}</p>
        <a class="sf-button" href="{{ route('ecommerce.checkout.show', $store->slug) }}">@lang('ecommerce::lang.try_again')</a>
    @endif
</section>
@endsection
