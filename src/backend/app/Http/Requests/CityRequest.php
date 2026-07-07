<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam name string required The name of the village.
 * @bodyParam mini_grid_id int required The id of the mini-grid the village belongs to.
 * @bodyParam country_id int required The id of the country the village belongs to.
 * @bodyParam geo_json object required The GPS location of the village as a GeoJSON Point Feature.
 */
class CityRequest extends FormRequest {
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
        return [
            'name' => ['required'],
            'mini_grid_id' => ['required'],
            'country_id' => ['required', 'integer', 'exists:tenant.countries,id'],
            'geo_json' => ['required', 'array', new GeoJsonPoint()],
        ];
    }
}
