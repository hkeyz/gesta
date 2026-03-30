@extends('ecommerce::layouts.storefront')
@section('title', 'My orders')

@section('storefront_content')
<section class="sf-section sf-panel">
    <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap;">
        <h2 style="margin:0;">My orders</h2>
        <form method="POST" action="{{ route('ecommerce.account.logout', $store->slug) }}">
            @csrf
            <button class="sf-pill" type="submit">Sign out</button>
        </form>
    </div>

    @if($orders->count())
        <table style="margin-top: 18px;">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->invoice_no }}</td>
                        <td>{{ ucfirst($order->status) }} / {{ ucfirst($order->payment_status ?? 'due') }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->transaction_date)->format('Y-m-d H:i') }}</td>
                        <td>{{ number_format($order->final_total, 2) }}</td>
                        <td><a class="sf-pill" href="{{ route('ecommerce.account.orders.show', [$store->slug, $order->id]) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top: 18px;">{{ $orders->links() }}</div>
    @else
        <p class="sf-muted" style="margin-top: 16px;">No ecommerce orders found for this store yet.</p>
    @endif
</section>
@endsection