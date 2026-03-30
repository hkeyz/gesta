@extends('ecommerce::layouts.storefront')
@section('title', !empty($currentCategory) ? $currentCategory->name : 'Products')

@section('storefront_content')
<section class="sf-section sf-panel">
    <form method="GET" action="{{ !empty($currentCategory) ? route('ecommerce.storefront.category', [$store->slug, $currentCategory->slug]) : route('ecommerce.storefront.products', $store->slug) }}" class="sf-form-grid">
        <div>
            <label for="q">Search</label>
            <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Product name or keyword">
        </div>
        <div>
            <label for="brand_id">Brand</label>
            <select name="brand_id" id="brand_id">
                <option value="">All brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ (string) request('brand_id') === (string) $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="min_price">Min price</label>
            <input type="number" name="min_price" id="min_price" step="0.01" value="{{ request('min_price') }}">
        </div>
        <div>
            <label for="max_price">Max price</label>
            <input type="number" name="max_price" id="max_price" step="0.01" value="{{ request('max_price') }}">
        </div>
        <div>
            <label for="sort">Sort</label>
            <select name="sort" id="sort">
                <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name</option>
                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price low to high</option>
                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price high to low</option>
            </select>
        </div>
        <div style="display:flex; align-items:end; gap: 12px;">
            <label style="display:flex; align-items:center; gap:8px; margin-bottom: 10px;">
                <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}>
                In stock only
            </label>
            <button class="sf-button sf-button--accent" type="submit">Apply</button>
        </div>
    </form>
</section>

@if($categories->isNotEmpty())
<section class="sf-section">
    <div class="sf-nav">
        <a class="sf-pill" href="{{ route('ecommerce.storefront.products', $store->slug) }}">All</a>
        @foreach($categories as $category)
            <a class="sf-pill" href="{{ route('ecommerce.storefront.category', [$store->slug, $category->slug]) }}">{{ $category->name }}</a>
        @endforeach
    </div>
</section>
@endif

<section class="sf-section">
    <div class="sf-grid sf-grid--products">
        @forelse($products as $listing)
            <article class="sf-card">
                <a href="{{ route('ecommerce.storefront.product', [$store->slug, $listing->slug]) }}"><img src="{{ $listing->product->image_url }}" alt="{{ $listing->product->name }}"></a>
                <div class="sf-card__body">
                    <div class="sf-muted">{{ optional($listing->product->brand)->name }} · {{ optional($listing->product->category)->name }}</div>
                    <h3 style="margin: 8px 0 8px; font-size: 1.1rem;"><a href="{{ route('ecommerce.storefront.product', [$store->slug, $listing->slug]) }}">{{ $listing->product->name }}</a></h3>
                    @if(!empty($listing->excerpt))
                        <p class="sf-muted">{{ \Illuminate\Support\Str::limit($listing->excerpt, 100) }}</p>
                    @endif
                    <div style="display:flex; justify-content:space-between; align-items:center; gap: 10px; margin-top: 12px;">
                        <strong class="sf-price">{{ number_format((float) ($listing->min_price ?? 0), 2) }} {{ $store->business->currency->symbol ?? '$' }}</strong>
                        <span class="sf-muted">{{ !empty($listing->product->enable_stock) ? max(0, (float) ($listing->total_stock ?? 0)) . ' in stock' : 'Always available' }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="sf-panel">No products matched your filters.</div>
        @endforelse
    </div>
</section>

@if(method_exists($products, 'links'))
<section class="sf-section">
    {{ $products->links() }}
</section>
@endif
@endsection
