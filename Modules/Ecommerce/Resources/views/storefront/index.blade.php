@extends('ecommerce::layouts.storefront')
@section('title', $settings['brand_name'] ?? $store->business->name)

@section('storefront_content')
<section class="sf-hero sf-section">
    <p class="sf-muted" style="letter-spacing: .18em; text-transform: uppercase;">Storefront</p>
    <h2 style="font-size: clamp(2rem, 5vw, 4rem); margin: 0 0 12px;">Browse products and buy directly.</h2>
    <p class="sf-muted" style="font-size: 1.05rem; max-width: 720px;">Search products, filter by category, add to cart or use buy now for an express checkout path.</p>
    <div style="display:flex; gap: 12px; flex-wrap: wrap; margin-top: 20px;">
        <a class="sf-button sf-button--accent" href="{{ route('ecommerce.storefront.products', $store->slug) }}">Shop all products</a>
        @if($categories->isNotEmpty())
            <a class="sf-button" href="{{ route('ecommerce.storefront.category', [$store->slug, $categories->first()->slug]) }}">Start with {{ $categories->first()->name }}</a>
        @endif
    </div>
</section>

@if($categories->isNotEmpty())
<section class="sf-section">
    <div class="sf-panel">
        <h3 style="margin-top: 0;">Browse by category</h3>
        <div class="sf-nav">
            @foreach($categories as $category)
                <a class="sf-pill" href="{{ route('ecommerce.storefront.category', [$store->slug, $category->slug]) }}">{{ $category->name }}</a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="sf-section">
    <div style="display:flex; justify-content:space-between; align-items:end; gap: 16px; margin-bottom: 14px;">
        <div>
            <p class="sf-muted" style="margin:0; text-transform: uppercase; letter-spacing: .15em;">Latest</p>
            <h3 style="margin: 6px 0 0;">Featured products</h3>
        </div>
        <a class="sf-pill" href="{{ route('ecommerce.storefront.products', $store->slug) }}">View full catalog</a>
    </div>
    <div class="sf-grid sf-grid--products">
        @foreach($featuredProducts as $listing)
            <article class="sf-card">
                <a href="{{ route('ecommerce.storefront.product', [$store->slug, $listing->slug]) }}"><img src="{{ $listing->product->image_url }}" alt="{{ $listing->product->name }}"></a>
                <div class="sf-card__body">
                    <div class="sf-muted">{{ optional($listing->product->category)->name }}</div>
                    <h4 style="margin: 8px 0 10px;"><a href="{{ route('ecommerce.storefront.product', [$store->slug, $listing->slug]) }}">{{ $listing->product->name }}</a></h4>
                    <div class="sf-price">{{ number_format((float) ($listing->min_price ?? 0), 2) }} {{ $store->business->currency->symbol ?? '$' }}</div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection
