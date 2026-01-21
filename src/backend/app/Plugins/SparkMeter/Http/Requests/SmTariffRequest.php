<?php

namespace App\Plugins\SparkMeter\Http\Requests;

use App\Plugins\SparkMeter\Models\SmTariff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SmTariffRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        $meterTariff = SmTariff::with('mpmTariff')->where('tariff_id', $this->id)->firstOrFail();

        return [
            'name' => ['required', Rule::unique('tenant.meter_tariffs')->ignore($meterTariff->mpmTariff->id)],
            'flatPrice' => ['required'],
            'flatLoadLimit' => ['required'],
            'planEnabled' => ['required'],
            'touEnabled' => ['required'],
            'tous' => ['sometimes', 'array', 'required_if:touEnabled,1,true'],
            'tous.*.start' => ['required_with:tous'],
            'tous.*.end' => ['required_with:tous'],
            'tous.*.value' => ['required_with:tous'],
        ];
    }
}
