<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManufacturerRequest extends FormRequest
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
            'name' => 'required|string|unique:manufacturers',
            'phone' => 'sometimes|string',
            'email' => 'sometimes|email',
            'contact_person' => 'sometimes|min:3',
            'website' => 'sometimes|min:6',
            'city_id' => 'sometimes|integer|exists:cities,id',
            'address' => 'sometimes|string|required_with:city_id',
            'api_name' => 'sometimes|unique:manufacturers',
            'master_key' => 'required_with:api_name|in:' . (string)(config()->get('services.manufacturer_master_key'))
        ];
    }
}
