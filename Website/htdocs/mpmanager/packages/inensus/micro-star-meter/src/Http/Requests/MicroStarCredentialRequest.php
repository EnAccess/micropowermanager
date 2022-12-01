<?php


namespace Inensus\MicroStarMeter\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MicroStarCredentialRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => ['required'],
            'api_key' => ['required'],
        ];
    }
}