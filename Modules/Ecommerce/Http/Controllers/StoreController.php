<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Ecommerce\Entities\EcomApiSetting;
use Modules\Ecommerce\Entities\EcomProductListing;
use Modules\Ecommerce\Entities\EcomStore;
use Modules\Ecommerce\Services\StorefrontService;

class StoreController extends Controller
{
    public function __construct(protected StorefrontService $storefrontService)
    {
    }

    public function edit()
    {
        $this->authorizeStoreAccess();

        $businessId = session()->get('user.business_id');
        $business = Business::with('currency')->findOrFail($businessId);
        $store = EcomStore::firstOrNew(['business_id' => $businessId]);
        $settings = $this->storefrontService->getStoreSettings($store);
        $apiSetting = EcomApiSetting::firstOrNew(['business_id' => $businessId]);
        $locations = BusinessLocation::forDropdown($businessId);
        $publishedCount = $store->exists
            ? EcomProductListing::where('store_id', $store->id)->where('is_published', 1)->count()
            : 0;

        return view('ecommerce::admin.settings', compact(
            'business',
            'store',
            'settings',
            'apiSetting',
            'locations',
            'publishedCount'
        ));
    }

    public function update(Request $request)
    {
        $this->authorizeStoreAccess();

        $businessId = session()->get('user.business_id');
        $store = EcomStore::firstOrNew(['business_id' => $businessId]);

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:120', Rule::unique('ecom_stores', 'slug')->ignore($store->id)],
            'location_id' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'brand_name' => ['nullable', 'string', 'max:191'],
            'tagline' => ['nullable', 'string', 'max:191'],
            'accent_color' => ['nullable', 'string', 'max:32'],
            'enable_pickup' => ['nullable', 'boolean'],
            'enable_delivery' => ['nullable', 'boolean'],
            'flat_shipping_label' => ['nullable', 'string', 'max:191'],
            'flat_shipping_rate' => ['nullable', 'numeric', 'min:0'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $isEnabled = $request->boolean('is_enabled');
        if ($isEnabled && empty($validated['location_id'])) {
            return redirect()->back()->withErrors([
                'location_id' => 'A stock location is required before enabling the storefront.',
            ])->withInput();
        }

        $business = Business::findOrFail($businessId);
        $store->business_id = $businessId;
        $store->location_id = $validated['location_id'] ?? null;
        $store->slug = Str::slug($validated['slug']);
        $store->is_enabled = $isEnabled;
        $store->settings = [
            'brand_name' => $validated['brand_name'] ?: $business->name,
            'tagline' => $validated['tagline'] ?? null,
            'accent_color' => $validated['accent_color'] ?: '#1f6feb',
            'enable_pickup' => $request->boolean('enable_pickup', true),
            'enable_delivery' => $request->boolean('enable_delivery', true),
            'flat_shipping_label' => $validated['flat_shipping_label'] ?: 'Standard delivery',
            'flat_shipping_rate' => (float) ($validated['flat_shipping_rate'] ?? 0),
            'stripe_webhook_secret' => $validated['stripe_webhook_secret'] ?? null,
            'slides' => $store->settings['slides'] ?? [],
        ];
        $store->save();

        $apiSetting = EcomApiSetting::updateOrCreate(
            ['business_id' => $businessId],
            [
                'store_id' => $store->id,
                'api_token' => optional(EcomApiSetting::where('business_id', $businessId)->first())->api_token ?: Str::random(60),
                'shop_domain' => $store->slug,
                'is_active' => $store->is_enabled,
            ]
        );

        $this->storefrontService->ensureCategorySlugs($businessId);
        $this->storefrontService->syncBusinessEcomSettings($store, $apiSetting);

        return redirect()->route('ecommerce.settings')->with('status', [
            'success' => 1,
            'msg' => 'Storefront settings saved successfully.',
        ]);
    }

    protected function authorizeStoreAccess(): void
    {
        if (! auth()->check() || (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('ecommerce.manage'))) {
            abort(403, 'Unauthorized action.');
        }
    }
}
