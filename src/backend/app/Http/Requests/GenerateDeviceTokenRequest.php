<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DeviceTokenUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateDeviceTokenRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            // How much credit to issue, read in the given unit.
            'amount' => ['required', 'numeric', 'min:0.01'],
            // Whether the amount is a currency amount or a credit amount the device can carry.
            'unit' => ['required', Rule::enum(DeviceTokenUnit::class)],
        ];
    }

    public function unit(): DeviceTokenUnit {
        return $this->enum('unit', DeviceTokenUnit::class);
    }
}
