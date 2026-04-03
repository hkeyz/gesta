@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.create_account'))

@section('storefront_content')
<section class="sf-section sf-panel" style="max-width: 620px; margin-left:auto; margin-right:auto;">
    <h2 style="margin-top:0;">@lang('ecommerce::lang.create_account')</h2>
    <form method="POST" action="{{ route('ecommerce.account.register.store', $store->slug) }}" class="sf-form-grid">
        @csrf
        <div>
            <label for="first_name">@lang('ecommerce::lang.first_name')</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
        </div>
        <div>
            <label for="last_name">@lang('ecommerce::lang.last_name')</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}">
        </div>
        <div>
            <label for="email">@lang('ecommerce::lang.email')</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="phone">@lang('ecommerce::lang.phone')</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
        </div>
        <div>
            <label for="password">@lang('ecommerce::lang.password')</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password_confirmation">@lang('ecommerce::lang.confirm_password')</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <div style="grid-column: 1 / -1; display:flex; gap: 12px; align-items:center;">
            <button class="sf-button sf-button--accent" type="submit">@lang('ecommerce::lang.create_account')</button>
            <a class="sf-pill" href="{{ route('ecommerce.account.login', $store->slug) }}">@lang('ecommerce::lang.already_have_account')</a>
        </div>
    </form>
</section>
@endsection
