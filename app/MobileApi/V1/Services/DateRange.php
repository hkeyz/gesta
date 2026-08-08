<?php

namespace App\MobileApi\V1\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateRange
{
    public function fromRequest(Request $request): array
    {
        $key = $request->input('range', 'today');
        $now = Carbon::now();

        switch ($key) {
            case 'yesterday':
                $from = $now->copy()->subDay()->startOfDay();
                $to = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $from = $now->copy()->startOfWeek();
                $to = $now->copy()->endOfDay();
                break;
            case 'month':
                $from = $now->copy()->startOfMonth();
                $to = $now->copy()->endOfDay();
                break;
            case 'year':
                $from = $now->copy()->startOfYear();
                $to = $now->copy()->endOfDay();
                break;
            case 'last_7_days':
                $from = $now->copy()->subDays(6)->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'last_30_days':
                $from = $now->copy()->subDays(29)->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'custom':
                $from = Carbon::parse($request->input('from'))->startOfDay();
                $to = Carbon::parse($request->input('to'))->endOfDay();
                break;
            case 'today':
            default:
                $key = 'today';
                $from = $now->copy()->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
        }

        $seconds = max(1, $from->diffInSeconds($to));
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subSeconds($seconds);

        return [
            'key' => $key,
            'from' => $from,
            'to' => $to,
            'previous_from' => $previousFrom,
            'previous_to' => $previousTo,
        ];
    }

    public function serialize(array $range): array
    {
        return [
            'key' => $range['key'],
            'from' => $range['from']->toIso8601String(),
            'to' => $range['to']->toIso8601String(),
            'previous_from' => $range['previous_from']->toIso8601String(),
            'previous_to' => $range['previous_to']->toIso8601String(),
        ];
    }
}
