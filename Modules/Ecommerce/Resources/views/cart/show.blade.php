@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.cart'))

@section('storefront_content')
<section class="sf-section sf-panel">
    <h2 style="margin-top: 0;">@lang('ecommerce::lang.your_cart')</h2>
    @if(empty($cart['items']))
        <p class="sf-muted">@lang('ecommerce::lang.cart_empty')</p>
        <a class="sf-button sf-button--accent" href="{{ route('ecommerce.storefront.products', $store->slug) }}">@lang('ecommerce::lang.continue_shopping')</a>
    @else
        <form method="POST" action="{{ route('ecommerce.cart.update', $store->slug) }}">
            @csrf
            <table>
                <thead>
                    <tr>
                        <th>@lang('ecommerce::lang.product')</th>
                        <th>@lang('ecommerce::lang.price')</th>
                        <th>@lang('ecommerce::lang.quantity')</th>
                        <th>@lang('ecommerce::lang.total')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart['items'] as $item)
                        <tr>
                            <td>
                                <a href="{{ $item['product_url'] }}">{{ $item['product_name'] }}</a>
                                <div class="sf-muted">{{ $item['variation_name'] }}</div>
                                <div style="margin-top: 10px;">
                                    <button class="sf-pill" formaction="{{ route('ecommerce.cart.remove', [$store->slug, $item['variation_id']]) }}" type="submit">@lang('ecommerce::lang.remove')</button>
                                </div>
                            </td>
                            <td>{{ number_format($item['unit_price_inc_tax'], 2) }}</td>
                            <td>
                                <input type="number" min="1" name="quantities[{{ $item['variation_id'] }}]" value="{{ $item['quantity'] }}">
                                @if(!is_null($item['available_qty']))
                                    <div class="sf-muted">@lang('ecommerce::lang.available'): {{ max(0, $item['available_qty']) }}</div>
                                @endif
                            </td>
                            <td>{{ number_format($item['line_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="display:flex; justify-content:space-between; align-items:center; gap: 16px; margin-top: 20px; flex-wrap: wrap;">
                <div>
                    <div>@lang('ecommerce::lang.subtotal'): <strong>{{ number_format($cart['totals']['subtotal'], 2) }}</strong></div>
                </div>
                <div style="display:flex; gap: 12px;">
                    <button class="sf-button" type="submit">@lang('ecommerce::lang.update_cart')</button>
                    <a class="sf-button sf-button--accent" href="{{ route('ecommerce.checkout.show', $store->slug) }}">@lang('ecommerce::lang.proceed_to_checkout')</a>
                </div>
            </div>
        </form>
    @endif
</section>
@endsection
