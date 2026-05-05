<?php

namespace App\Http\Requests;

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
            'serial_number' => ['required', 'string', 'unique:tenant.meters,serial_number'],
            'manufacturer_id' => ['required', 'integer', 'exists:tenant.manufacturers,id'],
            'meter_type_id' => ['required', 'integer', 'exists:tenant.meter_types,id'],
            'tariff_id' => ['required', 'integer', 'exists:tenant.tariffs,id'],
            'connection_group_id' => ['required', 'integer', 'exists:tenant.connection_groups,id'],
            'connection_type_id' => ['required', 'integer', 'exists:tenant.connection_types,id'],
            'geo_points' => ['nullable', 'string'],
        ];
    }
}
