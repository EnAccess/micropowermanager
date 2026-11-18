<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenPaygoDeviceRegistrationRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array {
        return [
            'serial_number' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'unique:tenant.devices,device_serial',
            ],
            'manufacturer_id' => [
                'required',
                'integer',
                Rule::exists('tenant.manufacturers', 'id')
                    ->where('type', 'shs')
                    ->where('api_name', 'OpenPaygoApi'),
            ],
            'appliance_id' => [
                'required',
                'integer',
                Rule::exists('tenant.appliances', 'id')->where('appliance_type_id', 1),
            ],
            'person_id' => [
                'required',
                'integer',
                Rule::exists('tenant.people', 'id')->where('is_customer', true),
            ],
            'secretKeyHex' => [
                'required',
                'string',
                'regex:/\A[0-9a-fA-F]{32}\z/',
            ],
            'startingCode' => [
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ],
            'nextCounter' => [
                'required',
                'integer',
                'min:0',
                'max:100000',
            ],
            'timeDivider' => [
                'required',
                'integer',
                'min:1',
                'max:255',
            ],
        ];
    }

    public function messages(): array {
        return [
            'serial_number.unique' => 'This serial number is already registered.',
            'secretKeyHex.regex' => 'The OpenPAYGO secret key must contain exactly 32 hexadecimal characters.',
            'startingCode.integer' => 'The OpenPAYGO starting code must be an integer.',
            'nextCounter.integer' => 'The OpenPAYGO counter must be an integer.',
            'timeDivider.integer' => 'The OpenPAYGO time divider must be an integer.',
        ];
    }

    public function attributes(): array {
        return [
            'serial_number' => 'serial number',
            'appliance_id' => 'appliance',
            'manufacturer_id' => 'OpenPAYGO manufacturer',
            'person_id' => 'customer',
            'secretKeyHex' => 'OpenPAYGO secret key',
            'startingCode' => 'OpenPAYGO starting code',
            'nextCounter' => 'OpenPAYGO counter',
            'timeDivider' => 'OpenPAYGO time divider',
        ];
    }
}
