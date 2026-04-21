<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @urlParam clusterId required The ID of the cluster.
 *
 * @bodyParam name string The name of the cluster.
 * @bodyParam geo_json object GeoJSON polygon coordinates.
 * @bodyParam manager_id int The id of the user who is responsible for the cluster.
 */
class UpdateClusterRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

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

    /**
     * @return array<string, mixed>
     */
    public function getClusterData(): array {
        $data = $this->only(['name', 'manager_id', 'geo_json']);

        if (isset($data['geo_json']['properties'])
            && is_array($data['geo_json']['properties'])
            && empty($data['geo_json']['properties'])) {
            $data['geo_json']['properties'] = new \stdClass();
        }

        return $data;
    }
}
