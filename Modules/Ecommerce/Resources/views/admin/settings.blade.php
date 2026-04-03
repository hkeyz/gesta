@extends('layouts.app')
@section('title', __('ecommerce::lang.settings_title'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('ecommerce::lang.settings_title')</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('ecommerce::lang.storefront')</h3>
        </div>
        <form method="POST" action="{{ route('ecommerce.settings.update') }}">
            @csrf
            @method('PUT')
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="slug">@lang('ecommerce::lang.store_slug')</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $store->slug ?: \Illuminate\Support\Str::slug($business->name)) }}" required>
                            <p class="help-block">@lang('ecommerce::lang.public_url'): {{ url('/shop/' . ($store->slug ?: \Illuminate\Support\Str::slug($business->name))) }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="location_id">@lang('ecommerce::lang.stock_location')</label>
                            <select name="location_id" id="location_id" class="form-control">
                                <option value="">@lang('ecommerce::lang.select_location')</option>
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
                                @lang('ecommerce::lang.enable_storefront')
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="brand_name">@lang('ecommerce::lang.brand_name')</label>
                            <input type="text" class="form-control" id="brand_name" name="brand_name" value="{{ old('brand_name', $settings['brand_name'] ?? $business->name) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tagline">@lang('ecommerce::lang.tagline')</label>
                            <input type="text" class="form-control" id="tagline" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? '') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="accent_color">@lang('ecommerce::lang.accent_color')</label>
                            <input type="text" class="form-control" id="accent_color" name="accent_color" value="{{ old('accent_color', $settings['accent_color'] ?? '#1f6feb') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="enable_pickup" value="1" {{ old('enable_pickup', $settings['enable_pickup'] ?? true) ? 'checked' : '' }}>
                                @lang('ecommerce::lang.enable_pickup')
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="enable_delivery" value="1" {{ old('enable_delivery', $settings['enable_delivery'] ?? true) ? 'checked' : '' }}>
                                @lang('ecommerce::lang.enable_delivery')
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="flat_shipping_label">@lang('ecommerce::lang.delivery_label')</label>
                            <input type="text" class="form-control" id="flat_shipping_label" name="flat_shipping_label" value="{{ old('flat_shipping_label', $settings['flat_shipping_label'] ?? __('ecommerce::lang.standard_delivery')) }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="flat_shipping_rate">@lang('ecommerce::lang.flat_shipping_rate')</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="flat_shipping_rate" name="flat_shipping_rate" value="{{ old('flat_shipping_rate', $settings['flat_shipping_rate'] ?? 0) }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stripe_webhook_secret">@lang('ecommerce::lang.stripe_webhook_secret')</label>
                            <input type="text" class="form-control" id="stripe_webhook_secret" name="stripe_webhook_secret" value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret'] ?? '') }}">
                            <p class="help-block">@lang('ecommerce::lang.webhook_url'): {{ route('ecommerce.webhook.stripe', $store->slug ?: \Illuminate\Support\Str::slug($business->name)) }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('ecommerce::lang.api_token')</label>
                            <input type="text" class="form-control" value="{{ $apiSetting->api_token }}" readonly>
                            <p class="help-block">@lang('ecommerce::lang.published_products'): {{ $publishedCount }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">@lang('ecommerce::lang.save_storefront')</button>
                @if(!empty($store->slug))
                    <a href="{{ url('/shop/' . $store->slug) }}" class="btn btn-default">@lang('ecommerce::lang.open_storefront')</a>
                @endif
            </div>
        </form>
    </div>
</section>
@endsection
