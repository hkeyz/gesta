<?php

namespace Modules\MobileMoney\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MobileMoney\Services\MobileMoneyService;

abstract class BaseController extends Controller
{
    public function __construct(protected MobileMoneyService $mobileMoneyService)
    {
    }

    protected function isMobileMoneyEnabled(): bool
    {
        $enabledModules = session('business.enabled_modules', []);

        return in_array('mobile_money', is_array($enabledModules) ? $enabledModules : []);
    }

    protected function businessId(): int
    {
        return (int) session()->get('user.business_id');
    }

    protected function ensureBusinessSetup(): void
    {
        $this->mobileMoneyService->ensureBusinessSetup($this->businessId());
    }

    protected function authorizeMobileMoney(array $permissions): void
    {
        if (! auth()->check()) {
            abort(403, __('mobilemoney::lang.unauthorized_action'));
        }

        if (! $this->isMobileMoneyEnabled()) {
            abort(403, __('mobilemoney::lang.module_disabled'));
        }

        if (auth()->user()->can('manage_modules') || auth()->user()->can('business_settings.access')) {
            return;
        }

        foreach (array_unique($permissions) as $permission) {
            if (auth()->user()->can($permission)) {
                return;
            }
        }

        abort(403, __('mobilemoney::lang.unauthorized_action'));
    }
}
