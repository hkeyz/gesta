<?php

namespace App\MobileApi\V1\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class MobileApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'validation_failed',
                'message' => __('The given data was invalid.'),
                'details' => $validator->errors()->toArray(),
            ],
        ], 422));
    }
}
