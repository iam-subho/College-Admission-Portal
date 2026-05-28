<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Users\Models\OtpCode;

class OtpSendRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'purpose' => ['required', 'in:'.implode(',', [
                OtpCode::PURPOSE_LOGIN,
                OtpCode::PURPOSE_PASSWORD_RESET,
                OtpCode::PURPOSE_REGISTRATION,
            ])],
        ];
    }
}
