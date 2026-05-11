<?php

namespace App\Http\Requests;

use App\Models\Device;
use App\Models\Meter\Meter;
use Illuminate\Foundation\Http\FormRequest;

class AssignMeterToCustomerRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'serial_number' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $meter = Meter::query()->where('serial_number', $value)->first();
                    if ($meter !== null && (int) $meter->in_use === 1) {
                        $fail('This meter is already assigned to another customer.');

                        return;
                    }
                    $deviceTaken = Device::query()->where('device_serial', $value)->exists();
                    if ($deviceTaken) {
                        $fail('This meter is already assigned to another customer.');
                    }
                },
            ],
            'manufacturer_id' => ['required', 'integer', 'exists:tenant.manufacturers,id'],
            'meter_type_id' => ['required', 'integer', 'exists:tenant.meter_types,id'],
            'tariff_id' => ['required', 'integer', 'exists:tenant.tariffs,id'],
            'connection_group_id' => ['required', 'integer', 'exists:tenant.connection_groups,id'],
            'connection_type_id' => ['required', 'integer', 'exists:tenant.connection_types,id'],
            'geo_points' => ['nullable', 'string'],
        ];
    }
}
