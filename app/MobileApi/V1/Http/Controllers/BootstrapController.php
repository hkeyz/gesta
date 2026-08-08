<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Http\Controllers\Controller;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Support\ApiResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request, AccessContext $access)
    {
        $user = $request->user()->loadMissing(['business.currency']);
        $business = $user->business;
        $locationIds = $access->permittedLocationIds($user);
        $locations = $business->locations()
            ->whereIn('id', $locationIds ?: [0])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->map(fn ($location) => [
                'id' => (int) $location->id,
                'name' => $location->name,
                'location_id' => $location->location_id,
                'address' => $location->location_address,
            ])
            ->values();

        $permissions = $user->getAllPermissions()->pluck('name')->values();

        return $this->success([
            'user' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'full_name' => $user->user_full_name,
                'email' => $user->email,
                'language' => $user->language,
                'roles' => $user->getRoleNames()->map(fn ($role) => preg_replace('/#\d+$/', '', $role))->values(),
                'permissions' => $permissions,
                'image_url' => $user->image_url,
            ],
            'business' => [
                'id' => (int) $business->id,
                'name' => $business->name,
                'logo_url' => ! empty($business->logo) ? asset('uploads/business_logos/'.$business->logo) : null,
                'timezone' => $business->time_zone,
                'date_format' => $business->date_format,
                'currency' => [
                    'id' => (int) optional($business->currency)->id,
                    'code' => optional($business->currency)->code,
                    'symbol' => optional($business->currency)->symbol,
                    'decimal_separator' => optional($business->currency)->decimal_separator,
                    'thousand_separator' => optional($business->currency)->thousand_separator,
                    'precision' => (int) ($business->currency_precision ?? 2),
                    'symbol_placement' => $business->currency_symbol_placement,
                ],
            ],
            'locations' => $locations,
            'features' => [
                'dashboard' => $user->can('dashboard.data'),
                'sales' => $user->can('sell.view') || $user->can('sell.view_own'),
                'purchases' => $user->can('purchase.view') || $user->can('view_own_purchase'),
                'expenses' => $user->can('expense.access'),
                'inventory' => $user->can('stock_report.view') || $user->can('product.view'),
                'contacts' => $user->can('customer.view') || $user->can('supplier.view'),
                'cash_registers' => $user->can('view_cash_register'),
                'create_sale' => $user->can('sell.create'),
            ],
            'realtime' => [
                'dashboard_refresh_seconds' => 15,
                'activity_refresh_seconds' => 10,
            ],
            'api' => [
                'version' => 'v1',
                'server_time' => now()->toIso8601String(),
            ],
        ], $this->realtimeMeta());
    }
}
