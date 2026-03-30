<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateCustomer
{
    public function handle($request, Closure $next)
    {
        if (! Auth::guard('ecom_customer')->check()) {
            $storeSlug = $request->route('store_slug');
            session(['url.intended' => url()->full()]);

            return redirect()->route('ecommerce.account.login', $storeSlug);
        }

        return $next($request);
    }
}
