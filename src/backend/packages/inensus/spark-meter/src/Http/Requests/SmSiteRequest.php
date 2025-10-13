<?php

namespace Inensus\SparkMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmSiteRequest extends FormRequest {
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
            'thundercloud_url' => 'required',
            'thundercloud_token' => 'required',
        ];
    }
}
