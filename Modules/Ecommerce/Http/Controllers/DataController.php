<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Routing\Controller;
use Menu;
use Modules\Ecommerce\Entities\EcomStore;
use Modules\Ecommerce\Services\StorefrontService;

class DataController extends Controller
{
    public function modifyAdminMenu()
    {
        if (! auth()->check()) {
            return;
        }

        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('ecommerce.manage')) {
            return;
        }

        $businessId = session()->get('user.business_id');
        $store = ! empty($businessId) ? EcomStore::where('business_id', $businessId)->first() : null;

        Menu::modify('admin-sidebar-menu', function ($menu) use ($store) {
            $menu->dropdown(
                'E-commerce',
                function ($sub) use ($store) {
                    $sub->url(
                        action([\Modules\Ecommerce\Http\Controllers\StoreController::class, 'edit']),
                        'Store settings',
                        ['icon' => '', 'active' => request()->segment(1) === 'ecommerce' && request()->segment(2) === 'settings']
                    );

                    if (! empty($store?->slug)) {
                        $sub->url(
                            $store->public_url,
                            'Open storefront',
                            ['icon' => '', 'active' => false]
                        );
                    }
                },
                [
                    'icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M4 7l0 10"></path><path d="M20 7l0 10"></path><path d="M4 12l16 0"></path><path d="M4 7l4 -4"></path><path d="M20 7l-4 -4"></path><path d="M4 17l4 4"></path><path d="M20 17l-4 4"></path></svg>',
                    'active' => request()->segment(1) === 'ecommerce',
                ]
            )->order(58);
        });
    }

    public function user_permissions()
    {
        return [
            [
                'value' => 'ecommerce.manage',
                'label' => 'Manage ecommerce storefront',
                'default' => false,
            ],
            [
                'value' => 'ecommerce.orders',
                'label' => 'View ecommerce orders',
                'default' => false,
            ],
        ];
    }

    public function getAssets()
    {
        return [
            'js' => [],
            'css' => [],
        ];
    }

    public function product_form_part()
    {
        return [
            'template_path' => 'ecommerce::products.partials.publish_fields',
            'template_data' => app(StorefrontService::class)->productFormData(),
        ];
    }

    public function product_form_fields()
    {
        return [];
    }

    public function after_product_saved($data)
    {
        try {
            app(StorefrontService::class)->upsertListingForProduct($data['product'], $data['request']);
        } catch (\Throwable $e) {
            \Log::error('Ecommerce product sync failed: '.$e->getMessage());
        }
    }
}