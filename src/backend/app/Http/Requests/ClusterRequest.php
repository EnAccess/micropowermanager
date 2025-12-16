<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @urlParam  id required The ID of the post.
 * @urlParam  lang The language.
 *
 * @bodyParam name string required The name  of the cluster.
 * @bodyParam geo_json string required. GeoJSON polygon coordinates.
 * @bodyParam manager_id int required. The id of the user who is responsible for the cluster.
 */
class ClusterRequest extends FormRequest {
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
            'name' => ['required'],
            'geo_json' => ['required'],
            'manager_id' => ['required'],
        ];
    }

    /**
     * Get the cluster data from the request.
     *
     * @return array<string, mixed>
     */
    public function getClusterData(): array {
        $data = $this->only(['name', 'manager_id', 'geo_json']);

        // Fix empty properties to be an object
        if (isset($data['geo_json']['properties'])
            && is_array($data['geo_json']['properties'])
            && empty($data['geo_json']['properties'])) {
            $data['geo_json']['properties'] = new \stdClass();
        }

        return $data;
    }
}
