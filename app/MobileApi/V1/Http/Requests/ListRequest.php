<?php

namespace App\MobileApi\V1\Http\Requests;

class ListRequest extends MobileApiRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'search' => ['nullable', 'string', 'max:191'],
            'type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
            'payment_status' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'contact_type' => ['nullable', 'in:customer,supplier,both'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'stock_status' => ['nullable', 'in:in_stock,low_stock,out_of_stock,not_managed'],
        ];
    }
}
