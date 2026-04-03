<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Illuminate\Support\Facades\Artisan;
use Modules\Ecommerce\Services\StorefrontService;

class InstallController extends Controller
{
    public function __construct(protected StorefrontService $storefrontService)
    {
    }

    public function index()
    {
        return $this->installModule(__('ecommerce::lang.module_installed_successfully'));
    }

    public function update()
    {
        return $this->installModule(__('ecommerce::lang.module_updated_successfully'));
    }

    public function uninstall()
    {
        $this->authorizeModuleAction();
        System::removeProperty('ecommerce_version');

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => __('ecommerce::lang.module_disabled'),
        ]);
    }

    protected function installModule(string $message)
    {
        $this->authorizeModuleAction();

        try {
            Artisan::call('migrate', [
                '--path' => 'Modules/Ecommerce/Database/Migrations',
                '--force' => true,
            ]);

            System::addProperty('ecommerce_version', config('ecommerce.module_version'));
            $this->storefrontService->ensureCategorySlugs();

            $output = [
                'success' => 1,
                'msg' => $message,
            ];
        } catch (\Throwable $e) {
            \Log::error('Ecommerce install failed: '.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    protected function authorizeModuleAction(): void
    {
        if (! auth()->check() || (! auth()->user()->can('manage_modules') && ! auth()->user()->can('business_settings.access') && ! auth()->user()->can('ecommerce.manage'))) {
            abort(403, __('ecommerce::lang.unauthorized_action'));
        }
    }
}

