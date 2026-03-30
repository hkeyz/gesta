@extends('layouts.app')
@section('title', 'E-commerce Settings')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">E-commerce Settings</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Storefront</h3>
        </div>
        <form method="POST" action="{{ route('ecommerce.settings.update') }}">
            @csrf
            @method('PUT')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="slug">Store slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $store->slug ?: \Illuminate\Support\Str::slug($business->name)) }}" required>
                            <p class="help-block">Public URL: {{ url('/shop/' . ($store->slug ?: \Illuminate\Support\Str::slug($business->name))) }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="location_id">Stock location</label>
                            <select name="location_id" id="location_id" class="form-control">
                                <option value="">Select a location</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ (string) old('location_id', $store->location_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="checkbox" style="margin-top: 32px;">
                            <label>
                                <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $store->is_enabled) ? 'checked' : '' }}>
                                Enable storefront
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="brand_name">Brand name</label>
                            <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ old('brand_name', $settings['brand_name'] ?? $business->name) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tagline">Tagline</label>
                            <input type="text" class="form-control" id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="accent_color">Accent color</label>
                            <input type="text" class="form-control" id="accent_color" name="accent_color" value="{{ old('accent_color', $settings['accent_color'] ?? '#1f6feb') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="enable_pickup" value="1" {{ old('enable_pickup', $settings['enable_pickup'] ?? true) ? 'checked' : '' }}>
                                Enable pickup
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="enable_delivery" value="1" {{ old('enable_delivery', $settings['enable_delivery'] ?? true) ? 'checked' : '' }}>
                                Enable delivery
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="flat_shipping_label">Delivery label</label>
                            <input type="text" class="form-control" id="flat_shipping_label" name="flat_shipping_label" value="{{ old('flat_shipping_label', $settings['flat_shipping_label'] ?? 'Standard delivery') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="flat_shipping_rate">Flat shipping rate</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="flat_shipping_rate" name="flat_shipping_rate" value="{{ old('flat_shipping_rate', $settings['flat_shipping_rate'] ?? 0) }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stripe_webhook_secret">Stripe webhook secret</label>
                            <input type="text" class="form-control" id="stripe_webhook_secret" name="stripe_webhook_secret" value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret'] ?? '') }}">
                            <p class="help-block">Webhook URL: {{ route('ecommerce.webhook.stripe', $store->slug ?: \Illuminate\Support\Str::slug($business->name)) }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>API token</label>
                            <input type="text" class="form-control" value="{{ $apiSetting->api_token }}" readonly>
                            <p class="help-block">Published products: {{ $publishedCount }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Save storefront</button>
                @if(!empty($store->slug))
                    <a href="{{ url('/shop/' . $store->slug) }}" class="btn btn-default">Open storefront</a>
                @endif
            </div>
        </form>
    </div>
</section>
@endsection
