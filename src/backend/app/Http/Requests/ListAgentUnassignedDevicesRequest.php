<?php

namespace App\Http\Requests;

use App\Models\EBike;
use App\Models\SolarHomeSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAgentUnassignedDevicesRequest extends FormRequest {
    public const SUPPORTED_TYPES = [
        SolarHomeSystem::RELATION_NAME => SolarHomeSystem::class,
        EBike::RELATION_NAME => EBike::class,
    ];

    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'appliance_id' => ['required', 'integer', 'exists:tenant.appliances,id'],
            'type' => ['required', 'string', Rule::in(array_keys(self::SUPPORTED_TYPES))],
        ];
    }
}
