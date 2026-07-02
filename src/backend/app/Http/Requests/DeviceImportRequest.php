<?php

namespace App\Http\Requests;

use App\Services\ImportServices\DeviceImportItem;
use App\Services\ImportServices\DeviceInfoItem;
use App\Services\ImportServices\MeterTypeItem;
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
            'data' => ['required', 'array', 'list'],
            'data.*.customer' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info' => ['required', 'array'],
            'data.*.device_info.serial_number' => ['required', 'string', 'min:1'],
            'data.*.device_info.type' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.manufacturer' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.meter_type' => ['sometimes', 'nullable', 'array'],
            'data.*.device_info.meter_type.online' => ['sometimes', 'boolean'],
            'data.*.device_info.meter_type.phase' => ['sometimes', 'integer'],
            'data.*.device_info.meter_type.max_current' => ['sometimes', 'numeric'],
            'data.*.device_info.connection_type' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.connection_group' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.tariff' => ['sometimes', 'nullable', 'string'],
            'data.*.device_info.appliance' => ['sometimes', 'nullable', 'string'],
            'data.*.geo' => ['sometimes', 'nullable', 'array'],
            'data.*.geo.geo_json' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * @return list<DeviceImportItem>
     */
    public function items(): array {
        return array_map(function (array $item): DeviceImportItem {
            $info = $item['device_info'];
            $meterType = $info['meter_type'] ?? null;

            return new DeviceImportItem(
                customer: $item['customer'] ?? null,
                deviceInfo: new DeviceInfoItem(
                    serialNumber: $info['serial_number'],
                    type: $info['type'] ?? 'meter',
                    manufacturer: $info['manufacturer'] ?? null,
                    meterType: $meterType === null ? null : new MeterTypeItem(
                        online: (bool) ($meterType['online'] ?? false),
                        phase: (int) ($meterType['phase'] ?? 1),
                        maxCurrent: (float) ($meterType['max_current'] ?? 10),
                    ),
                    connectionType: $info['connection_type'] ?? null,
                    connectionGroup: $info['connection_group'] ?? null,
                    tariff: $info['tariff'] ?? null,
                    appliance: $info['appliance'] ?? null,
                ),
                geoJson: $item['geo']['geo_json'] ?? null,
            );
        }, $this->validated('data'));
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
