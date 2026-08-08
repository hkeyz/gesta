<?php

namespace App\MobileApi\V1\Http\Requests;

class LoginRequest extends MobileApiRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
