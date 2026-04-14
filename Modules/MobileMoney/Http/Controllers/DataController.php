<?php

namespace Modules\MobileMoney\Http\Controllers;

use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function modifyAdminMenu()
    {
        if (! auth()->check()) {
            return;
        }

        if (
            ! auth()->user()->can('mobile_money.access')
            && ! auth()->user()->can('mobile_money.transactions')
            && ! auth()->user()->can('mobile_money.reports')
            && ! auth()->user()->can('mobile_money.settings')
            && ! auth()->user()->can('mobile_money.operators')
            && ! auth()->user()->can('mobile_money.commissions')
            && ! auth()->user()->can('business_settings.access')
        ) {
            return;
        }

        Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('mobilemoney::lang.module_name'),
                function ($sub) {
                    if (auth()->user()->can('mobile_money.transactions') || auth()->user()->can('mobile_money.access') || auth()->user()->can('business_settings.access')) {
                        $sub->url(
                            action([\Modules\MobileMoney\Http\Controllers\TransactionController::class, 'index']),
                            __('mobilemoney::lang.operations'),
                            ['icon' => '', 'active' => request()->segment(1) === 'mobile-money' && request()->segment(2) === 'transactions']
                        );
                    }

                    if (auth()->user()->can('mobile_money.reports') || auth()->user()->can('mobile_money.access') || auth()->user()->can('business_settings.access')) {
                        $sub->url(
                            action([\Modules\MobileMoney\Http\Controllers\ReportController::class, 'index']),
                            __('mobilemoney::lang.reports'),
                            ['icon' => '', 'active' => request()->segment(1) === 'mobile-money' && request()->segment(2) === 'reports']
                        );
                    }

                    if (auth()->user()->can('mobile_money.operators') || auth()->user()->can('mobile_money.settings') || auth()->user()->can('business_settings.access')) {
                        $sub->url(
                            action([\Modules\MobileMoney\Http\Controllers\OperatorController::class, 'index']),
                            __('mobilemoney::lang.operators'),
                            ['icon' => '', 'active' => request()->segment(1) === 'mobile-money' && request()->segment(2) === 'operators']
                        );
                    }

                    if (auth()->user()->can('mobile_money.commissions') || auth()->user()->can('mobile_money.settings') || auth()->user()->can('business_settings.access')) {
                        $sub->url(
                            action([\Modules\MobileMoney\Http\Controllers\CommissionRuleController::class, 'index']),
                            __('mobilemoney::lang.commission_rules'),
                            ['icon' => '', 'active' => request()->segment(1) === 'mobile-money' && request()->segment(2) === 'commission-rules']
                        );
                    }

                    if (auth()->user()->can('mobile_money.settings') || auth()->user()->can('business_settings.access')) {
                        $sub->url(
                            action([\Modules\MobileMoney\Http\Controllers\SettingsController::class, 'edit']),
                            __('mobilemoney::lang.settings'),
                            ['icon' => '', 'active' => request()->segment(1) === 'mobile-money' && request()->segment(2) === 'settings']
                        );
                    }
                },
                [
                    'icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M7 9m0 1a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path><path d="M15 9m0 1a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path><path d="M9 15l6 0"></path><path d="M5 5m0 2a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M15 5m0 2a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M5 17m0 2a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M15 17m0 2a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path></svg>',
                    'active' => request()->segment(1) === 'mobile-money',
                ]
            )->order(59);
        });
    }

    public function user_permissions()
    {
        return [
            [
                'value' => 'mobile_money.access',
                'label' => __('mobilemoney::lang.access_module'),
                'default' => false,
            ],
            [
                'value' => 'mobile_money.transactions',
                'label' => __('mobilemoney::lang.manage_operations'),
                'default' => false,
            ],
            [
                'value' => 'mobile_money.cancel',
                'label' => __('mobilemoney::lang.cancel_operations'),
                'default' => false,
            ],
            [
                'value' => 'mobile_money.reports',
                'label' => __('mobilemoney::lang.view_reports'),
                'default' => false,
            ],
            [
                'value' => 'mobile_money.operators',
                'label' => __('mobilemoney::lang.manage_operators'),
                'default' => false,
            ],
            [
                'value' => 'mobile_money.commissions',
                'label' => __('mobilemoney::lang.manage_commission_rules'),
                'default' => false,
            ],
            [
                'value' => 'mobile_money.settings',
                'label' => __('mobilemoney::lang.manage_settings'),
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
}
