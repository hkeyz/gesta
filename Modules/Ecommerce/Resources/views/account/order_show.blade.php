@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.order_details'))

@section('storefront_content')
<section class="sf-section sf-panel">
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
    <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap;">
        <div>
            <h2 style="margin:0;">{{ $order->invoice_no }}</h2>
            <p class="sf-muted" style="margin:6px 0 0;">{{ $orderStatusLabel }} / {{ $paymentStatusLabel }}</p>
        </div>
        <a class="sf-pill" href="{{ route('ecommerce.account.orders', $store->slug) }}">@lang('ecommerce::lang.back_to_orders')</a>
    </div>

    <table style="margin-top: 18px;">
        <thead>
            <tr>
                <th>@lang('ecommerce::lang.item')</th>
                <th>@lang('ecommerce::lang.qty')</th>
                <th>@lang('ecommerce::lang.unit')</th>
                <th>@lang('ecommerce::lang.total')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->sell_lines as $line)
                <tr>
                    <td>{{ optional($line->product)->name }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ number_format($line->unit_price_inc_tax, 2) }}</td>
                    <td>{{ number_format($line->unit_price_inc_tax * $line->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->payment_lines->count())
        <h3 style="margin-top: 24px;">@lang('ecommerce::lang.payments')</h3>
        <table>
            <thead>
                <tr>
                    <th>@lang('ecommerce::lang.method')</th>
                    <th>@lang('ecommerce::lang.paid_on')</th>
                    <th>@lang('ecommerce::lang.amount')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payment_lines as $payment)
                    @php
                        $paymentMethodKey = 'ecommerce::lang.payment_method_' . ($payment->method ?? '');
                        $paymentMethodLabel = __($paymentMethodKey);
                        if ($paymentMethodLabel === $paymentMethodKey) {
                            $paymentMethodLabel = ucfirst((string) $payment->method);
                        }
                    @endphp
                    <tr>
                        <td>{{ $paymentMethodLabel }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->paid_on)->format('Y-m-d H:i') }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
