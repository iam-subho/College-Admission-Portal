<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
