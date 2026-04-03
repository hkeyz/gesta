@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.checkout'))

@section('storefront_content')
<section class="sf-section" style="display:grid; grid-template-columns: minmax(0, 1fr) minmax(320px, .75fr); gap: 24px; align-items:start;">
    <form method="POST" action="{{ route('ecommerce.checkout.store', $store->slug) }}" class="sf-panel">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <h2 style="margin-top:0;">@lang('ecommerce::lang.checkout')</h2>

        <div class="sf-form-grid">
            <div>
                <label for="first_name">@lang('ecommerce::lang.first_name')</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $prefill['first_name'] ?? '') }}">
            </div>
            <div>
                <label for="last_name">@lang('ecommerce::lang.last_name')</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $prefill['last_name'] ?? '') }}">
            </div>
            <div>
                <label for="email">@lang('ecommerce::lang.email')</label>
                <input type="email" id="email" name="email" value="{{ old('email', $prefill['email'] ?? '') }}">
            </div>
            <div>
                <label for="phone">@lang('ecommerce::lang.phone')</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $prefill['phone'] ?? '') }}">
            </div>
        </div>

        <div class="sf-section">
            <h3>@lang('ecommerce::lang.delivery')</h3>
            <label style="display:flex; gap: 8px; margin-bottom: 10px;"><input type="radio" name="shipping_method" value="pickup" {{ old('shipping_method', 'pickup') === 'pickup' ? 'checked' : '' }}> @lang('ecommerce::lang.pick_up_in_store')</label>
            <label style="display:flex; gap: 8px; margin-bottom: 14px;"><input type="radio" name="shipping_method" value="delivery" {{ old('shipping_method') === 'delivery' ? 'checked' : '' }}> @lang('ecommerce::lang.delivery')</label>
            <div class="sf-form-grid">
                <div>
                    <label for="address_line_1">@lang('ecommerce::lang.address_line_1')</label>
                    <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $prefill['address_line_1'] ?? '') }}">
                </div>
                <div>
                    <label for="address_line_2">@lang('ecommerce::lang.address_line_2')</label>
                    <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $prefill['address_line_2'] ?? '') }}">
                </div>
                <div>
                    <label for="city">@lang('ecommerce::lang.city')</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $prefill['city'] ?? '') }}">
                </div>
                <div>
                    <label for="state">@lang('ecommerce::lang.state')</label>
                    <input type="text" id="state" name="state" value="{{ old('state', $prefill['state'] ?? '') }}">
                </div>
                <div>
                    <label for="country">@lang('ecommerce::lang.country')</label>
                    <input type="text" id="country" name="country" value="{{ old('country', $prefill['country'] ?? '') }}">
                </div>
                <div>
                    <label for="zip_code">@lang('ecommerce::lang.zip_code')</label>
                    <input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code', $prefill['zip_code'] ?? '') }}">
                </div>
            </div>
        </div>

        <div class="sf-section">
            <h3>@lang('ecommerce::lang.payment')</h3>
            @foreach($paymentMethods as $method => $label)
                <label style="display:flex; gap: 8px; margin-bottom: 10px;"><input type="radio" name="payment_method" value="{{ $method }}" {{ old('payment_method', array_key_first($paymentMethods)) === $method ? 'checked' : '' }}> {{ $label }}</label>
            @endforeach
        </div>

        @guest('ecom_customer')
            <div class="sf-section">
                <label style="display:flex; gap:8px; margin-bottom: 10px;"><input type="checkbox" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}> @lang('ecommerce::lang.create_account_future_orders')</label>
                <div>
                    <label for="password">@lang('ecommerce::lang.password')</label>
                    <input type="password" id="password" name="password">
                </div>
            </div>
        @endguest

        <div class="sf-section">
            <label for="notes">@lang('ecommerce::lang.order_notes')</label>
            <textarea id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
        </div>

        <button class="sf-button sf-button--accent" type="submit">@lang('ecommerce::lang.place_order')</button>
    </form>

    <aside class="sf-panel">
        <h3 style="margin-top:0;">@lang('ecommerce::lang.order_summary')</h3>
        <table>
            <tbody>
                @foreach($checkout['items'] as $item)
                    <tr>
                        <td>{{ $item['product_name'] }}<br><span class="sf-muted">{{ $item['variation_name'] }} x {{ $item['quantity'] }}</span></td>
                        <td>{{ number_format($item['line_total'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>@lang('ecommerce::lang.subtotal')</td>
                    <td>{{ number_format($checkout['totals']['subtotal'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </aside>
</section>
@endsection
