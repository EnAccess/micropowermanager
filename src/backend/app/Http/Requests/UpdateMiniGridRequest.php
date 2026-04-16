<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @urlParam miniGridId required The ID of the mini-grid.
 *
 * @bodyParam name string The name of the mini-grid.
 * @bodyParam cluster_id int The id of the cluster that owns the mini-grid.
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
        ];
    }
}
