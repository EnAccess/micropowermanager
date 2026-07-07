<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

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
            // The name of the village.
            'name' => ['required'],
            // The id of the mini-grid the village belongs to.
            'mini_grid_id' => ['required'],
            // The id of the country the village belongs to.
            'country_id' => ['required', 'integer', 'exists:tenant.countries,id'],
            // The GPS location of the village as a GeoJSON Point Feature.
            'geo_json' => ['required', 'array', new GeoJsonPoint()],
        ];
    }
}
