@extends('ecommerce::layouts.storefront')
@section('title', 'Create account')

@section('storefront_content')
<section class="sf-section sf-panel" style="max-width: 620px; margin-left:auto; margin-right:auto;">
    <h2 style="margin-top:0;">Create account</h2>
    <form method="POST" action="{{ route('ecommerce.account.register.store', $store->slug) }}" class="sf-form-grid">
        @csrf
        <div>
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
        </div>
        <div>
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}">
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <div style="grid-column: 1 / -1; display:flex; gap: 12px; align-items:center;">
            <button class="sf-button sf-button--accent" type="submit">Create account</button>
            <a class="sf-pill" href="{{ route('ecommerce.account.login', $store->slug) }}">I already have an account</a>
        </div>
    </form>
</section>
@endsection
