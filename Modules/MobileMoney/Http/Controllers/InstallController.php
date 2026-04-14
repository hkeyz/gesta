<?php

namespace Modules\MobileMoney\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\Artisan;
use Modules\MobileMoney\Services\MobileMoneyService;

class InstallController extends Controller
{
    public function __construct(protected MobileMoneyService $mobileMoneyService)
    {
    }

    public function index()
    {
        return $this->installModule(__('mobilemoney::lang.module_installed_successfully'));
    }

    public function update()
    {
        return $this->installModule(__('mobilemoney::lang.module_updated_successfully'));
    }

    public function uninstall()
    {
        $this->authorizeModuleAction();
        System::removeProperty('mobilemoney_version');

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.module_disabled'),
        ]);
    }

    protected function installModule(string $message)
    {
        $this->authorizeModuleAction();

        try {
            Artisan::call('migrate', [
                '--path' => 'Modules/MobileMoney/Database/Migrations',
                '--force' => true,
            ]);

            System::addProperty('mobilemoney_version', config('mobilemoney.module_version'));
            $this->mobileMoneyService->ensureBusinessSetup(session()->get('user.business_id'));

            $output = [
                'success' => 1,
                'msg' => $message,
            ];
        } catch (\Throwable $e) {
            \Log::error('Mobile money install failed: '.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    protected function authorizeModuleAction(): void
    {
        if (! auth()->check() || (! auth()->user()->can('manage_modules') && ! auth()->user()->can('business_settings.access') && ! auth()->user()->can('mobile_money.settings'))) {
            abort(403, __('mobilemoney::lang.unauthorized_action'));
        }
    }
}
