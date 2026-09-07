<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DeviceTokenType;
use App\Enums\DeviceTokenUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateDeviceTokenRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * `amount` and `unit` are read through {@see self::type()} rather than
     * `required_if:type,credit`, because that rule stays silent when `type` is
     * omitted and a request without a type is a credit request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        $isCreditRequest = fn (): bool => $this->type() === DeviceTokenType::Credit;

        return [
            // What the token should do to the device. Defaults to issuing credit.
            'type' => ['sometimes', Rule::enum(DeviceTokenType::class)],
            // How much credit to issue, read in the given unit. Only for a credit token.
            'amount' => [Rule::requiredIf($isCreditRequest), 'numeric', 'min:0.01'],
            // Whether the amount is a currency amount or a credit amount the device can carry.
            'unit' => [Rule::requiredIf($isCreditRequest), Rule::enum(DeviceTokenUnit::class)],
        ];
    }

    public function type(): DeviceTokenType {
        return $this->enum('type', DeviceTokenType::class) ?? DeviceTokenType::Credit;
    }

    public function unit(): DeviceTokenUnit {
        return $this->enum('unit', DeviceTokenUnit::class);
    }
}
