<?php

namespace App\Http\Requests;

use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMiniGridRequest extends FormRequest
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
        $sessionService = app()->make(SessionService::class);
        $database=$sessionService->getAuthenticatedUserDatabaseName();
        return [
            'name' => 'required|min:3',
            'cluster_id' => 'required|exists:'.$database.'.clusters,id',
            'geo_data' => 'required',
        ];
    }
}
