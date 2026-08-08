<?php

namespace App\MobileApi\V1\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMobileAccountIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (empty($user)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => __('Unauthenticated.'),
                ],
            ], 401);
        }

        $user->loadMissing('business');

        if (empty($user->business)) {
            return $this->forbidden('business_not_found', __('No business is associated with this account.'));
        }

        if (! $user->business->is_active) {
            return $this->forbidden('business_inactive', __('The business is inactive.'));
        }

        if ($user->status !== 'active') {
            return $this->forbidden('user_inactive', __('The user is inactive.'));
        }

        if (! $user->allow_login) {
            return $this->forbidden('login_not_allowed', __('Login is not allowed for this user.'));
        }

        $timezone = $user->business->time_zone ?: config('app.timezone');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        if (! empty($user->language)) {
            app()->setLocale($user->language);
        }

        $request->attributes->set('mobile_business_id', (int) $user->business_id);

        return $next($request);
    }

    protected function forbidden(string $code, string $message)
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], 403);
    }
}
