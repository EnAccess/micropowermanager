<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMiniGridRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            // The name of the mini-grid.
            'name' => ['sometimes', 'string', 'min:1'],
            // The id of the cluster that owns the mini-grid.
            'cluster_id' => ['sometimes', 'integer', 'exists:tenant.clusters,id'],
            // The GPS location of the mini-grid as a GeoJSON Point Feature.
            'geo_json' => ['sometimes', 'array', new GeoJsonPoint()],
        ];
    }
}
