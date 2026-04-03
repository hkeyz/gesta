@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.my_orders'))

@section('storefront_content')
<section class="sf-section sf-panel">
    <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap;">
        <h2 style="margin:0;">@lang('ecommerce::lang.my_orders')</h2>
        <form method="POST" action="{{ route('ecommerce.account.logout', $store->slug) }}">
            @csrf
            <button class="sf-pill" type="submit">@lang('ecommerce::lang.sign_out')</button>
        </form>
    </div>

    @if($orders->count())
        <table style="margin-top: 18px;">
            <thead>
                <tr>
                    <th>@lang('ecommerce::lang.reference')</th>
                    <th>@lang('ecommerce::lang.status')</th>
                    <th>@lang('ecommerce::lang.date')</th>
                    <th>@lang('ecommerce::lang.total')</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    @php
                        $orderStatusKey = 'ecommerce::lang.order_status_' . ($order->status ?? '');
                        $paymentStatusKey = 'ecommerce::lang.payment_status_' . ($order->payment_status ?? 'due');
                        $orderStatusLabel = __($orderStatusKey);
                        $paymentStatusLabel = __($paymentStatusKey);
                        if ($orderStatusLabel === $orderStatusKey) {
                            $orderStatusLabel = ucfirst((string) $order->status);
                        }
                        if ($paymentStatusLabel === $paymentStatusKey) {
                            $paymentStatusLabel = ucfirst((string) ($order->payment_status ?? 'due'));
                        }
                    @endphp
                    <tr>
                        <td>{{ $order->invoice_no }}</td>
                        <td>{{ $orderStatusLabel }} / {{ $paymentStatusLabel }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->transaction_date)->format('Y-m-d H:i') }}</td>
                        <td>{{ number_format($order->final_total, 2) }}</td>
                        <td><a class="sf-pill" href="{{ route('ecommerce.account.orders.show', [$store->slug, $order->id]) }}">@lang('ecommerce::lang.view')</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 18px;">{{ $orders->links() }}</div>
    @else
        <p class="sf-muted" style="margin-top: 16px;">@lang('ecommerce::lang.no_orders_yet')</p>
    @endif
</section>
@endsection
