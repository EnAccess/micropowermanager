<?php

namespace App\Http\Requests;

use App\Rules\GeoJsonPoint;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @urlParam miniGridId required The ID of the mini-grid.
 *
 * @bodyParam name string The name of the mini-grid.
 * @bodyParam cluster_id int The id of the cluster that owns the mini-grid.
 * @bodyParam geo_json object The GPS location of the mini-grid as a GeoJSON Point Feature.
 */
class UpdateMiniGridRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['sometimes', 'string', 'min:1'],
            'cluster_id' => ['sometimes', 'integer', 'exists:tenant.clusters,id'],
            'geo_json' => ['sometimes', 'array', new GeoJsonPoint()],
        ];
    }
}
