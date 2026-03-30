@extends('ecommerce::layouts.storefront')
@section('title', 'Order details')

@section('storefront_content')
<section class="sf-section sf-panel">
    <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap;">
        <div>
            <h2 style="margin:0;">{{ $order->invoice_no }}</h2>
            <p class="sf-muted" style="margin:6px 0 0;">{{ ucfirst($order->status) }} / {{ ucfirst($order->payment_status ?? 'due') }}</p>
        </div>
        <a class="sf-pill" href="{{ route('ecommerce.account.orders', $store->slug) }}">Back to orders</a>
    </div>

    <table style="margin-top: 18px;">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Total</th>
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
        <h3 style="margin-top: 24px;">Payments</h3>
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Paid on</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payment_lines as $payment)
                    <tr>
                        <td>{{ ucfirst($payment->method) }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->paid_on)->format('Y-m-d H:i') }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection