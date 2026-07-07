<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            // The name of the cluster.
            'name' => ['required'],
            // GeoJSON polygon coordinates.
            'geo_json' => ['required'],
            // The id of the user who is responsible for the cluster.
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
