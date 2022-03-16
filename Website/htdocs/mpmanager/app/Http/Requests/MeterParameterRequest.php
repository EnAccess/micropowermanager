<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class MeterParameterRequest
 *
 * @package App\Http\Requests
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
            'meter_id' => 'required|exists:meters,id,in_use,0', //meter should be exist and un used
            'tariff_id' => 'required|exists:meter_tariffs,id',
            'customer_id' => 'required|exists:roles,role_owner_id',
            'geo_points' => 'required',
        ];
    }
}
