<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class MeterParameterRequest.
 *
 * @bodyParam meter_id int required
 * @bodyParam tariff_id int required
 * @bodyParam customer_id int required
 * @bodyParam geo_points string required
 */
class MeterParameterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'meter_id' => 'required',
            'tariff_id' => 'required',
            'customer_id' => 'required',
            'connection_type_id' => 'required',
            'connection_group_id' => 'required',
            'geo_points' => 'required',
        ];
    }
}
