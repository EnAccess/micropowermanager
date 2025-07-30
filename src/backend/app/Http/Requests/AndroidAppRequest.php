<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AndroidAppRequest extends FormRequest {
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
     * @return array<string, mixed>
     */
    public function rules() {
        return [
            'name' => 'required|min:3',
            'surname' => 'required|min:3',
            'phone' => 'required|min:11|regex:(^\+)|numeric',
            'tariff_id' => 'required',
            'geo_points' => 'required',
            'serial_number' => 'required|string',
            'manufacturer' => 'required',
            'meter_type' => 'required',
        ];
    }
}
