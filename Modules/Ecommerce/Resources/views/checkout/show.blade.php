@extends('ecommerce::layouts.storefront')
@section('title', 'Checkout')

@section('storefront_content')
<section class="sf-section" style="display:grid; grid-template-columns: minmax(0, 1fr) minmax(320px, .75fr); gap: 24px; align-items:start;">
    <form method="POST" action="{{ route('ecommerce.checkout.store', $store->slug) }}" class="sf-panel">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <h2 style="margin-top:0;">Checkout</h2>

        <div class="sf-form-grid">
            <div>
                <label for="first_name">First name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $prefill['first_name'] ?? '') }}">
            </div>
            <div>
                <label for="last_name">Last name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $prefill['last_name'] ?? '') }}">
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $prefill['email'] ?? '') }}">
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $prefill['phone'] ?? '') }}">
            </div>
        </div>

        <div class="sf-section">
            <h3>Delivery</h3>
            <label style="display:flex; gap: 8px; margin-bottom: 10px;"><input type="radio" name="shipping_method" value="pickup" {{ old('shipping_method', 'pickup') === 'pickup' ? 'checked' : '' }}> Pick up in store</label>
            <label style="display:flex; gap: 8px; margin-bottom: 14px;"><input type="radio" name="shipping_method" value="delivery" {{ old('shipping_method') === 'delivery' ? 'checked' : '' }}> Delivery</label>
            <div class="sf-form-grid">
                <div>
                    <label for="address_line_1">Address line 1</label>
                    <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $prefill['address_line_1'] ?? '') }}">
                </div>
                <div>
                    <label for="address_line_2">Address line 2</label>
                    <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $prefill['address_line_2'] ?? '') }}">
                </div>
                <div>
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $prefill['city'] ?? '') }}">
                </div>
                <div>
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="{{ old('state', $prefill['state'] ?? '') }}">
                </div>
                <div>
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="{{ old('country', $prefill['country'] ?? '') }}">
                </div>
                <div>
                    <label for="zip_code">ZIP code</label>
                    <input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code', $prefill['zip_code'] ?? '') }}">
                </div>
            </div>
        </div>

        <div class="sf-section">
            <h3>Payment</h3>
            @foreach($paymentMethods as $method => $label)
                <label style="display:flex; gap: 8px; margin-bottom: 10px;"><input type="radio" name="payment_method" value="{{ $method }}" {{ old('payment_method', array_key_first($paymentMethods)) === $method ? 'checked' : '' }}> {{ $label }}</label>
            @endforeach
        </div>

        @guest('ecom_customer')
            <div class="sf-section">
                <label style="display:flex; gap:8px; margin-bottom: 10px;"><input type="checkbox" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}> Create an account for future orders</label>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password">
                </div>
            </div>
        @endguest

        <div class="sf-section">
            <label for="notes">Order notes</label>
            <textarea id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
        </div>

        <button class="sf-button sf-button--accent" type="submit">Place order</button>
    </form>

    <aside class="sf-panel">
        <h3 style="margin-top:0;">Order summary</h3>
        <table>
            <tbody>
                @foreach($checkout['items'] as $item)
                    <tr>
                        <td>{{ $item['product_name'] }}<br><span class="sf-muted">{{ $item['variation_name'] }} × {{ $item['quantity'] }}</span></td>
                        <td>{{ number_format($item['line_total'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>Subtotal</td>
                    <td>{{ number_format($checkout['totals']['subtotal'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </aside>
</section>
@endsection
