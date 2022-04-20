<?php

namespace App\Http\Requests;

use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class MeterRequest extends FormRequest
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
            'serial_number' => 'required|string|unique:'.$database.'.meters,serial_number',
            'manufacturer_id' => 'required|exists:'.$database.'.manufacturers,id',
            'meter_type_id' => 'required|exists:'.$database.'.meter_types,id',
            // 'action' => 'sometimes|in:meters.new,meters.detail,meters.list',
        ];
    }
}
