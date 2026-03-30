<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticatedCustomer
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('ecom_customer')->check()) {
            return redirect()->route('ecommerce.account.orders', $request->route('store_slug'));
        }

        return $next($request);
    }
}
