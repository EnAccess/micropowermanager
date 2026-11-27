<?php

namespace App\Plugins\AirtelPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberUtil;

class AirtelCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        $callingCodes = array_map(
            fn (int $callingCode): string => '+'.$callingCode,
            PhoneNumberUtil::getInstance()->getSupportedCallingCodes(),
        );

        return [
            'jwt_secret' => ['required', 'string'],
            'phone_prefix' => ['required', 'string', 'regex:/^\+\d+$/', Rule::in($callingCodes)],
        ];
    }
}
