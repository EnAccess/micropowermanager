<?php

namespace App\Http\Requests;

use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $sessionService = app()->make(SessionService::class);
        $database=$sessionService->getAuthenticatedUserDatabaseName();
        return [
            'name' => 'required|unique:'.$database.'.cities',
            'mini_grid_id' => 'required|exists:'.$database.'.mini_grids,id'
        ];
    }
}
