<?php

namespace App\Http\Requests;

/**
 * @urlParam clusterId required The ID of the cluster.
 *
 * @bodyParam name string The name of the cluster.
 * @bodyParam geo_json object GeoJSON polygon coordinates.
 * @bodyParam manager_id int The id of the user who is responsible for the cluster.
 */
class UpdateClusterRequest extends ClusterRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['sometimes', 'string', 'min:1'],
            'geo_json' => ['sometimes'],
            'manager_id' => ['sometimes', 'integer'],
        ];
    }
}
