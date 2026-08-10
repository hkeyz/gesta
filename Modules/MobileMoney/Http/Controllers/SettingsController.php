<?php

namespace Modules\MobileMoney\Http\Controllers;

use Illuminate\Http\Request;
use Modules\MobileMoney\Entities\MmSetting;

class SettingsController extends BaseController
{
    public function edit()
    {
        $this->authorizeMobileMoney(['mobile_money.settings', 'business_settings.access']);
        $this->ensureBusinessSetup();

        $settings = MmSetting::where('business_id', $this->businessId())->firstOrFail();

        return view('mobilemoney::settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorizeMobileMoney(['mobile_money.settings', 'business_settings.access']);
        $this->ensureBusinessSetup();

        $validated = $request->validate([
            'terminal_label' => 'required|string|max:255',
            'terminal_number' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:1000',
        ]);

        $settings = MmSetting::where('business_id', $this->businessId())->firstOrFail();
        $settings->update([
            'terminal_label' => $validated['terminal_label'],
            'terminal_number' => $validated['terminal_number'] ?? null,
            'receipt_footer' => $validated['receipt_footer'] ?? null,
            'auto_assign_reference' => $request->boolean('auto_assign_reference', true),
            'allow_manual_commission' => $request->boolean('allow_manual_commission', true),
        ]);

        return redirect()->route('mobilemoney.settings.edit')->with('status', [
            'success' => 1,
            'msg' => __('mobilemoney::lang.settings_saved'),
        ]);
    }
}
