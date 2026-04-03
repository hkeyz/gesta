@extends('ecommerce::layouts.storefront')
@section('title', __('ecommerce::lang.sign_in'))

@section('storefront_content')
<section class="sf-section sf-panel" style="max-width: 520px; margin-left:auto; margin-right:auto;">
    <h2 style="margin-top:0;">@lang('ecommerce::lang.sign_in')</h2>
    <form method="POST" action="{{ route('ecommerce.account.login.store', $store->slug) }}" style="display:grid; gap: 14px;">
        @csrf
        <div>
            <label for="email">@lang('ecommerce::lang.email')</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="password">@lang('ecommerce::lang.password')</label>
            <input type="password" id="password" name="password" required>
        </div>
        <label style="display:flex; gap:8px;"><input type="checkbox" name="remember" value="1"> @lang('ecommerce::lang.remember_me')</label>
        <button class="sf-button sf-button--accent" type="submit">@lang('ecommerce::lang.sign_in')</button>
    </form>
    <p style="margin-top: 16px;" class="sf-muted">@lang('ecommerce::lang.no_account_yet') <a href="{{ route('ecommerce.account.register', $store->slug) }}">@lang('ecommerce::lang.create_one')</a></p>
</section>
@endsection
