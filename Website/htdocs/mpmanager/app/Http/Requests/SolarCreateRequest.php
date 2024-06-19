<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolarCreateRequest extends FormRequest
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
            'node_id' => 'required',
            'device_id' => 'required',
            'mini_grid_id' => 'required',
            'solar_reading.starting_time' => 'required',
            'solar_reading.readings' => 'required',
            'solar_reading.average' => 'required',
            'solar_reading.total' => 'required',
            'time_stamp' => 'required',
        ];
    }
}
