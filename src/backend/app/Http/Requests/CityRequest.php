<?php

namespace App\Http\Requests;

use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest {
    private const PARAM_NAME = 'name';
    private const PARAM_MINI_GRID = 'mini_grid_id';
    private const PARAM_CLUSTER_ID = 'cluster_id';
    private const PARAM_COUNTRY_ID = 'country_id';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'name' => 'required',
            'mini_grid_id' => 'required',
            'cluster_id' => 'required',
            'country_id' => 'required|integer|exists:countries,id',
        ];
    }
}
