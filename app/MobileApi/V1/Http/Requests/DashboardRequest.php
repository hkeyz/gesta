<?php

namespace App\MobileApi\V1\Http\Requests;

class DashboardRequest extends MobileApiRequest
{
    public function rules(): array
    {
        return [
            'range' => ['nullable', 'in:today,yesterday,week,month,year,last_7_days,last_30_days,custom'],
            'from' => ['required_if:range,custom', 'nullable', 'date'],
            'to' => ['required_if:range,custom', 'nullable', 'date', 'after_or_equal:from'],
            'location_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
