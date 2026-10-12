
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenPaygoDeviceConfigurationRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

  
    public function rules(): array
    {
        return [
            'secretKeyHex' => [
                'required',
                'string',
                'regex:/\A[0-9a-fA-F]{32}\z/',
            ],
            'startingCode' => [
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ],
            'nextCounter' => [
                'required',
                'integer',
                'min:0',
                'max:100000',
            ],
            // Optional. When omitted or null, the service preserves an
            // existing divider; a new configuration may leave it null.
            'timeDivider' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:255',
            ],
        ];
    }
}
