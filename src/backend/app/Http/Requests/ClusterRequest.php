<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @urlParam  id required The ID of the post.
 * @urlParam  lang The language.
 *
 * @bodyParam name string required The name  of the cluster.
 * @bodyParam geo_type string required. Describes if the polygon created manually or fetched from the internet.
 * @bodyParam geo_data string required. Coordinates or the external url  that contains a geojson.
 * @bodyParam cities array[int] required. The id's of the cities which belong to the cluster.
 * @bodyParam manager_id int required. The id of the user who is responsible for the cluster.
 */
class ClusterRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'name' => 'required',
            'geo_type' => 'required',
            'geo_data' => 'required',
            'manager_id' => 'required',
        ];
    }
}
