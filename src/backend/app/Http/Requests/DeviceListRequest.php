<?php

namespace App\Http\Requests;

use App\Enums\DeviceType;
use App\Enums\ManufacturerMappingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceListRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            // Filter by device type.
            'device_type' => ['sometimes', Rule::enum(DeviceType::class)],
            // Filter by the appliance the device belongs to.
            'appliance_id' => ['sometimes', 'integer'],
            // When true, only devices not assigned to a customer are returned.
            'unassigned' => ['sometimes', 'boolean'],
            // Filter by (partial) device serial number.
            'serial' => ['sometimes', 'string'],
            // Filter by the outcome of the last manufacturer mapping check.
            'manufacturer_mapping_status' => ['sometimes', Rule::enum(ManufacturerMappingStatus::class)],
            // The number of items per page.
            'per_page' => ['sometimes', 'integer'],
        ];
    }

    /**
     * @return array{device_type: DeviceType|null, appliance_id: int, unassigned: bool, serial: string, manufacturer_mapping_status: ManufacturerMappingStatus|null}
     */
    public function filters(): array {
        return [
            'device_type' => $this->enum('device_type', DeviceType::class),
            'appliance_id' => $this->integer('appliance_id'),
            'unassigned' => $this->boolean('unassigned'),
            'serial' => $this->string('serial')->toString(),
            'manufacturer_mapping_status' => $this->enum('manufacturer_mapping_status', ManufacturerMappingStatus::class),
        ];
    }
}
