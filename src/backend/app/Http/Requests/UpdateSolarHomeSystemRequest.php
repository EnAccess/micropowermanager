<?php

namespace App\Http\Requests;

use App\Enums\DeviceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSolarHomeSystemRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|ValidationRule|\Stringable>>
     */
    public function rules(): array {
        $solarHomeSystemId = (int) $this->route('id');

        return [
            'serial_number' => [
                'sometimes',
                'string',
                'min:8',
                'max:15',
                Rule::unique('tenant.devices', 'device_serial')->where(
                    fn (Builder $query) => $query->whereNot(
                        fn (Builder $ownDevice) => $ownDevice
                            ->where('device_type', DeviceType::SolarHomeSystem)
                            ->where('device_id', $solarHomeSystemId)
                    )
                ),
            ],
            'manufacturer_id' => ['required', 'exists:tenant.manufacturers,id'],
            'appliance_id' => ['required', 'exists:tenant.appliances,id'],
        ];
    }
}
