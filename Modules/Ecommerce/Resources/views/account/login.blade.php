@extends('ecommerce::layouts.storefront')
@section('title', 'Sign in')

@section('storefront_content')
<section class="sf-section sf-panel" style="max-width: 520px; margin-left:auto; margin-right:auto;">
    <h2 style="margin-top:0;">Sign in</h2>
    <form method="POST" action="{{ route('ecommerce.account.login.store', $store->slug) }}" style="display:grid; gap: 14px;">
        @csrf
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <label style="display:flex; gap:8px;"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="sf-button sf-button--accent" type="submit">Sign in</button>
    </form>
    <p style="margin-top: 16px;" class="sf-muted">No account yet? <a href="{{ route('ecommerce.account.register', $store->slug) }}">Create one</a></p>
</section>
@endsection
