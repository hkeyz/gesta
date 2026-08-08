<?php

namespace App\MobileApi\V1\Http\Requests;

class ActivityRequest extends MobileApiRequest
{
    public function rules(): array
    {
        return [
            'since' => ['nullable', 'date'],
            'after_id' => ['nullable', 'integer', 'min:0'],
            'before' => ['nullable', 'date'],
            'before_id' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'types' => ['nullable', 'string', 'max:250'],
        ];
    }
}
