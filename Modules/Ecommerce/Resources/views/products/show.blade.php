@extends('ecommerce::layouts.storefront')
@section('title', $product->name)

@section('storefront_content')
<section class="sf-section" style="display:grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 24px;">
    <article class="sf-card">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
    </article>
    <article class="sf-panel">
        <p class="sf-muted">{{ optional($product->brand)->name }} | {{ optional($product->category)->name }}</p>
        <h2 style="margin: 6px 0 12px;">{{ $product->name }}</h2>
        @if(!empty($listing->excerpt))
            <p>{{ $listing->excerpt }}</p>
        @elseif(!empty($product->product_description))
            <p>{{ strip_tags($product->product_description) }}</p>
        @endif

        <form method="POST" action="{{ route('ecommerce.cart.add', $store->slug) }}" style="display:grid; gap: 14px; margin-top: 18px;">
            @csrf
            <div>
                <label for="variation_id">@lang('ecommerce::lang.variation')</label>
                <select name="variation_id" id="variation_id" required>
                    @foreach($variations as $variation)
                        <option value="{{ $variation->id }}">
                            {{ $variation->option_name }} | {{ number_format((float) $variation->sell_price_inc_tax, 2) }} {{ $store->business->currency->symbol ?? '$' }}
                            @if(!empty($product->enable_stock))
                                | {{ max(0, (float) $variation->stock_qty) }} @lang('ecommerce::lang.in_stock')
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quantity">@lang('ecommerce::lang.quantity')</label>
                <input type="number" min="1" name="quantity" id="quantity" value="1">
            </div>
            <div style="display:flex; gap: 12px; flex-wrap: wrap;">
                <button class="sf-button sf-button--accent" type="submit">@lang('ecommerce::lang.add_to_cart')</button>
                <button class="sf-button" formaction="{{ route('ecommerce.buy_now.store', $store->slug) }}" type="submit">@lang('ecommerce::lang.buy_now')</button>
            </div>
        </form>
    </article>
</section>

@if($relatedProducts->isNotEmpty())
<section class="sf-section">
    <h3>@lang('ecommerce::lang.related_products')</h3>
    <div class="sf-grid sf-grid--products">
        @foreach($relatedProducts as $related)
            <article class="sf-card">
                <a href="{{ route('ecommerce.storefront.product', [$store->slug, $related->slug]) }}"><img src="{{ $related->product->image_url }}" alt="{{ $related->product->name }}"></a>
                <div class="sf-card__body">
                    <h4 style="margin: 0 0 8px;">{{ $related->product->name }}</h4>
                    <div class="sf-price">{{ number_format((float) ($related->min_price ?? 0), 2) }} {{ $store->business->currency->symbol ?? '$' }}</div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif
@endsection
