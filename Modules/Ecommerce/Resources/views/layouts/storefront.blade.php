<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', ($settings['brand_name'] ?? $store->business->name))</title>
    <style>
        :root {
            --accent: {{ $settings['accent_color'] ?? '#1f6feb' }};
            --ink: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --panel: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            color: var(--ink);
            background: radial-gradient(circle at top left, rgba(31,111,235,0.12), transparent 28%), linear-gradient(180deg, #fcfcfd, #f2f5f9);
        }
        a { color: inherit; text-decoration: none; }
        .sf-shell { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
        .sf-topbar { padding: 22px 0; display: flex; gap: 16px; justify-content: space-between; align-items: center; }
        .sf-brand h1 { margin: 0; font-size: 1.8rem; }
        .sf-brand p { margin: 4px 0 0; color: var(--muted); }
        .sf-nav { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
        .sf-pill, .sf-button, button.sf-button {
            border: 1px solid var(--line);
            background: var(--panel);
            border-radius: 999px;
            padding: 10px 16px;
            font: inherit;
            cursor: pointer;
        }
        .sf-button--accent {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .sf-hero {
            background: #fff;
            border: 1px solid rgba(17,24,39,0.06);
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.06);
        }
        .sf-grid { display: grid; gap: 22px; }
        .sf-grid--products { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .sf-card {
            background: var(--panel);
            border: 1px solid rgba(17,24,39,0.08);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.04);
        }
        .sf-card__body { padding: 18px; }
        .sf-card img { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; display: block; background: #f3f4f6; }
        .sf-price { font-size: 1.1rem; font-weight: bold; }
        .sf-muted { color: var(--muted); }
        .sf-section { margin: 28px 0; }
        .sf-panel {
            background: var(--panel);
            border: 1px solid rgba(17,24,39,0.08);
            border-radius: 22px;
            padding: 22px;
        }
        .sf-form-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea, select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px 14px;
            font: inherit;
            background: #fff;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--line); text-align: left; }
        .sf-flash { margin: 16px 0; padding: 14px 16px; border-radius: 16px; background: rgba(31,111,235,0.1); border: 1px solid rgba(31,111,235,0.18); }
        .sf-error { margin: 16px 0; padding: 14px 16px; border-radius: 16px; background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.18); }
        .sf-foot { padding: 36px 0 48px; color: var(--muted); }
        @media (max-width: 768px) {
            .sf-topbar { flex-direction: column; align-items: flex-start; }
            .sf-hero { padding: 24px; }
        }
    </style>
</head>
<body>
    <header class="sf-shell sf-topbar">
        <div class="sf-brand">
            <a href="{{ route('ecommerce.storefront.home', $store->slug) }}"><h1>{{ $settings['brand_name'] ?? $store->business->name }}</h1></a>
            @if(!empty($settings['tagline']))
                <p>{{ $settings['tagline'] }}</p>
            @endif
        </div>
        <nav class="sf-nav">
            <a class="sf-pill" href="{{ route('ecommerce.storefront.products', $store->slug) }}">@lang('ecommerce::lang.products')</a>
            <a class="sf-pill" href="{{ route('ecommerce.cart.show', $store->slug) }}">{{ __('ecommerce::lang.cart_with_count', ['count' => $cartCount ?? 0]) }}</a>
            @auth('ecom_customer')
                <a class="sf-pill" href="{{ route('ecommerce.account.orders', $store->slug) }}">@lang('ecommerce::lang.my_account')</a>
            @else
                <a class="sf-pill" href="{{ route('ecommerce.account.login', $store->slug) }}">@lang('ecommerce::lang.sign_in')</a>
            @endauth
        </nav>
    </header>

    <main class="sf-shell">
        @if(session('status.msg'))
            <div class="sf-flash">{{ session('status.msg') }}</div>
        @endif
        @if($errors->any())
            <div class="sf-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('storefront_content')
    </main>

    <footer class="sf-shell sf-foot">
        <div>{{ $settings['brand_name'] ?? $store->business->name }} | @lang('ecommerce::lang.powered_by')</div>
    </footer>
</body>
</html>
