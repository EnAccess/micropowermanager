<?php

namespace App\Http\Requests;

use App\Services\ImportServices\ClusterImportItem;
use Illuminate\Foundation\Http\FormRequest;

class ClusterImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array', 'list'],
            'data.*.cluster_name' => ['required', 'string', 'min:1'],
            'data.*.manager' => ['sometimes', 'nullable', 'string'],
            'data.*.mini_grids' => ['sometimes', 'nullable', 'string'],
            'data.*.villages' => ['sometimes', 'nullable', 'string'],
            'data.*.geo_json' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * @return list<ClusterImportItem>
     */
    public function items(): array {
        return array_map(fn (array $item): ClusterImportItem => new ClusterImportItem(
            clusterName: $item['cluster_name'],
            manager: $item['manager'] ?? null,
            geoJson: $item['geo_json'] ?? null,
            miniGrids: $item['mini_grids'] ?? null,
            villages: $item['villages'] ?? null,
        ), $this->validated('data'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.cluster_name.required' => 'Each cluster must have a name.',
            'data.*.cluster_name.string' => 'Cluster name must be a string.',
        ];
    }
}
