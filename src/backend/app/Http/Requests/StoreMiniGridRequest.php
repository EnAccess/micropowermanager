<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam name string required The name of the mini-grid.
 * @bodyParam cluster_id int required The id of the cluster that owns the mini-grid.
 * @bodyParam geo_json object required The GPS location of the mini-grid as a GeoJSON Point Feature.
 */
class StoreMiniGridRequest extends FormRequest {
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
            'name' => ['required', 'min:3'],
            'cluster_id' => ['required'],
            'geo_json' => ['required', 'array', new GeoJsonPoint()],
        ];
    }
}
