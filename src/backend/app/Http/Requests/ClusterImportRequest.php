<?php

namespace App\Http\Requests;

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
