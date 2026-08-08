<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Http\Controllers\Controller;
use App\MobileApi\V1\Http\Requests\DashboardRequest;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Services\DashboardService;
use App\MobileApi\V1\Services\DateRange;
use App\MobileApi\V1\Support\ApiResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(
        DashboardRequest $request,
        AccessContext $access,
        DateRange $dateRange,
        DashboardService $dashboard
    ) {
        if (! $request->user()->can('dashboard.data')) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $range = $dateRange->fromRequest($request);
        $locationIds = $access->permittedLocationIds($request->user());
        $locationId = $access->selectedLocationId($request, $locationIds);

        $data = $dashboard->build(
            $access->businessId($request),
            $range,
            $locationIds,
            $locationId
        );
        $data['range'] = $dateRange->serialize($range);
        $data['location_id'] = $locationId;

        return $this->success($data, $this->realtimeMeta(15));
    }
}
