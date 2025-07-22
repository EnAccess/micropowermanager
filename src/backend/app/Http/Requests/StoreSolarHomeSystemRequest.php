<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolarHomeSystemRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'serial_number' => 'required|min:8|max:11|unique:tenant.devices,device_serial',
            'manufacturer_id' => 'required|exists:tenant.manufacturers,id',
            'asset_id' => 'required|exists:tenant.assets,id',
        ];
    }
}
