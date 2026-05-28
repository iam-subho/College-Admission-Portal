<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,strict', 'max:150', 'unique:users,email'],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/\d/'],
            'dpdp_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'password.regex' => 'Password must contain at least one uppercase letter and one digit.',
            'dpdp_consent.accepted' => 'You must accept the data protection consent to continue.',
        ];
    }
}
