<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentAssignedApplianceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'agent_id' => 'required|exists:agents,id',
            'user_id' => 'required|exists:users,id',
            'appliance_type_id' => 'required|exists:asset_types,id',
            'cost' => 'required|regex:/^\d*(\.\d{1,2})?$/',
        ];
    }
}
