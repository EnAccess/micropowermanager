<?php

namespace App\Http\Requests;

class UpdateClusterRequest extends ClusterRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            // The name of the cluster.
            'name' => ['sometimes', 'string', 'min:1'],
            // GeoJSON polygon coordinates.
            'geo_json' => ['sometimes'],
            // The id of the user who is responsible for the cluster.
            'manager_id' => ['sometimes', 'integer'],
        ];
    }
}
