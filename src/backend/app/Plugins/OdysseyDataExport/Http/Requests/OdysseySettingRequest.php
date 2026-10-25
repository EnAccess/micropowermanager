<?php

namespace App\Plugins\OdysseyDataExport\Http\Requests;

use App\Plugins\OdysseyDataExport\Models\OdysseyFinancingProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OdysseySettingRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'financing_id' => ['present', 'nullable', 'string', Rule::enum(OdysseyFinancingProgram::class)],
        ];
    }
}
