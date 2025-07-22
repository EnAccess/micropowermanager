<?php

namespace App\Http\Requests;

use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgentChargeRequest extends FormRequest {
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
        // TODO: Change on UI.  user_id is not required.
        $sessionService = app()->make(SessionService::class);
        $database = $sessionService->getAuthenticatedUserDatabaseName();

        return [
            'agent_id' => 'required',
            'amount' => 'required|numeric',
        ];
    }
}
