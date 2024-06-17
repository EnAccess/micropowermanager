<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PVRequest extends FormRequest
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
            'mini_grid_id' => 'required|exists:shard.mini_grids,id',
            'node_id' => 'required',
            'device_id' => 'required',
        ];
    }
}
