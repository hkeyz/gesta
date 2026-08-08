<?php

namespace App\MobileApi\V1\Services;

use App\BusinessLocation;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class AccessContext
{
    public function businessId(Request $request): int
    {
        return (int) ($request->attributes->get('mobile_business_id') ?: $request->user()->business_id);
    }

    public function permittedLocationIds(User $user): array
    {
        $query = BusinessLocation::query()
            ->where('business_id', $user->business_id)
            ->where('is_active', 1);

        if (! $user->can('access_all_locations')) {
            $permissionIds = $user->getAllPermissions()
                ->pluck('name')
                ->filter(fn ($permission) => str_starts_with($permission, 'location.'))
                ->map(fn ($permission) => (int) substr($permission, strlen('location.')))
                ->filter()
                ->values()
                ->all();

            $query->whereIn('id', $permissionIds ?: [0]);
        }

        return $query->orderBy('name')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function selectedLocationId(Request $request, array $permittedLocationIds): ?int
    {
        if (! $request->filled('location_id')) {
            return null;
        }

        $locationId = (int) $request->input('location_id');
        if (! in_array($locationId, $permittedLocationIds, true)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'validation_failed',
                    'message' => __('The given data was invalid.'),
                    'details' => [
                        'location_id' => [
                            __('You do not have access to this business location.'),
                        ],
                    ],
                ],
            ], 422));
        }

        return $locationId;
    }

    public function applyLocationScope(
        Builder|QueryBuilder $query,
        string $column,
        array $permittedLocationIds,
        ?int $selectedLocationId = null
    ): Builder|QueryBuilder {
        if (empty($permittedLocationIds)) {
            return $query->whereRaw('1 = 0');
        }

        if (! empty($selectedLocationId)) {
            return $query->where($column, $selectedLocationId);
        }

        return $query->whereIn($column, $permittedLocationIds);
    }
}
