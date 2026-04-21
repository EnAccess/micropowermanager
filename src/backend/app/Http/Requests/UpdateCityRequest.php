<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @urlParam cityId required The ID of the village (city).
 *
 * @bodyParam name string The name of the village.
 * @bodyParam mini_grid_id int The id of the mini-grid the village belongs to.
 * @bodyParam country_id int The id of the country the village belongs to.
 */
class UpdateCityRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['sometimes', 'string', 'min:1'],
            'mini_grid_id' => ['sometimes', 'integer', 'exists:tenant.mini_grids,id'],
            'country_id' => ['sometimes', 'integer', 'exists:tenant.countries,id'],
        ];
    }
}
