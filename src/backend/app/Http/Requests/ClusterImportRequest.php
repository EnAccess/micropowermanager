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
            'data' => ['required', 'array'],
            'data.*.cluster_name' => ['required', 'string', 'min:1'],
            'data.*.manager' => ['sometimes', 'nullable', 'string'],
            'data.*.mini_grids' => ['sometimes', 'nullable', 'string'],
            'data.*.villages' => ['sometimes', 'nullable', 'string'],
            'data.*.created_at' => ['sometimes', 'nullable', 'string'],
            'data.*.updated_at' => ['sometimes', 'nullable', 'string'],
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
