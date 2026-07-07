<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

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
            // The name of the mini-grid.
            'name' => ['required', 'min:3'],
            // The id of the cluster that owns the mini-grid.
            'cluster_id' => ['required'],
            // The GPS location of the mini-grid as a GeoJSON Point Feature.
            'geo_json' => ['required', 'array', new GeoJsonPoint()],
        ];
    }
}
