<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            // The name of the village.
            'name' => ['sometimes', 'string', 'min:1'],
            // The id of the mini-grid the village belongs to.
            'mini_grid_id' => ['sometimes', 'integer', 'exists:tenant.mini_grids,id'],
            // The id of the country the village belongs to.
            'country_id' => ['sometimes', 'integer', 'exists:tenant.countries,id'],
            // The GPS location of the village as a GeoJSON Point Feature.
            'geo_json' => ['sometimes', 'array', new GeoJsonPoint()],
        ];
    }
}
