<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array'],
            'data.*.id' => ['sometimes', 'nullable', 'integer'],
            'data.*.customer' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info' => ['required', 'array'],
            'data.*.device_info.type' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.manufacturer' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.serial_number' => ['required', 'string', 'min:1'],
            'data.*.tokens' => ['sometimes', 'nullable', 'array'],
            'data.*.geo' => ['sometimes', 'nullable', 'array'],
            'data.*.geo.points' => ['sometimes', 'nullable', 'string'],
            'data.*.created_at' => ['sometimes', 'nullable', 'string'],
            'data.*.updated_at' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.device_info.required' => 'Each device must have device info.',
            'data.*.device_info.array' => 'Device info must be an array.',
            'data.*.device_info.serial_number.required' => 'Each device must have a serial number.',
        ];
    }
}
