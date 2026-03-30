@extends('ecommerce::layouts.storefront')
@section('title', 'Cart')

@section('storefront_content')
<section class="sf-section sf-panel">
    <h2 style="margin-top: 0;">Your cart</h2>
    @if(empty($cart['items']))
        <p class="sf-muted">Your cart is empty.</p>
        <a class="sf-button sf-button--accent" href="{{ route('ecommerce.storefront.products', $store->slug) }}">Continue shopping</a>
    @else
        <form method="POST" action="{{ route('ecommerce.cart.update', $store->slug) }}">
            @csrf
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart['items'] as $item)
                        <tr>
                            <td>
                                <a href="{{ $item['product_url'] }}">{{ $item['product_name'] }}</a>
                                <div class="sf-muted">{{ $item['variation_name'] }}</div>
                                <div style="margin-top: 10px;">
                                    <button class="sf-pill" formaction="{{ route('ecommerce.cart.remove', [$store->slug, $item['variation_id']]) }}" type="submit">Remove</button>
                                </div>
                            </td>
                            <td>{{ number_format($item['unit_price_inc_tax'], 2) }}</td>
                            <td>
                                <input type="number" min="1" name="quantities[{{ $item['variation_id'] }}]" value="{{ $item['quantity'] }}">
                                @if(!is_null($item['available_qty']))
                                    <div class="sf-muted">Available: {{ max(0, $item['available_qty']) }}</div>
                                @endif
                            </td>
                            <td>{{ number_format($item['line_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="display:flex; justify-content:space-between; align-items:center; gap: 16px; margin-top: 20px; flex-wrap: wrap;">
                <div>
                    <div>Subtotal: <strong>{{ number_format($cart['totals']['subtotal'], 2) }}</strong></div>
                </div>
                <div style="display:flex; gap: 12px;">
                    <button class="sf-button" type="submit">Update cart</button>
                    <a class="sf-button sf-button--accent" href="{{ route('ecommerce.checkout.show', $store->slug) }}">Proceed to checkout</a>
                </div>
            </div>
        </form>
    @endif
</section>
@endsection
