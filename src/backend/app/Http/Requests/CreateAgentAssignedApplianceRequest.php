<?php

namespace App\Http\Requests;

use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgentAssignedApplianceRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        $sessionService = app()->make(SessionService::class);
        $database = $sessionService->getAuthenticatedUserDatabaseName();

        return [
            'agent_id' => 'required',
            'user_id' => 'required',
            'appliance_id' => 'required',
            'cost' => 'required|regex:/^\d*(\.\d{1,2})?$/',
        ];
    }
}
