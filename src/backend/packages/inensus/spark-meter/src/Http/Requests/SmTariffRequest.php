<?php

namespace Inensus\SparkMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Inensus\SparkMeter\Models\SmTariff;

class SmTariffRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        $meterTariff = SmTariff::with('mpmTariff')->where('tariff_id', $this->id)->firstOrFail();

        return [
            'name' => ['required', Rule::unique('tenant.meter_tariffs')->ignore($meterTariff->mpmTariff->id)],
            'flatPrice' => 'required',
            'flatLoadLimit' => 'required',
            'planEnabled' => 'required',
            'touEnabled' => 'required',
            'tous' => 'sometimes|array|required_if:touEnabled,1,true',
            'tous.*.start' => 'required_with:tous',
            'tous.*.end' => 'required_with:tous',
            'tous.*.value' => 'required_with:tous',
        ];
    }
}
