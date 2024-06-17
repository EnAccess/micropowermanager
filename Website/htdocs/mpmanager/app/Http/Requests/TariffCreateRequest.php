<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TariffCreateRequest extends FormRequest
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
            'name' => 'required',
            'price' => 'required|numeric', // 100 times of original price to support 2 decimal numbers.
            'currency' => 'required|string|max:20',
            'factor' => 'sometimes|integer',
            'access_rate_period' => 'integer|min:1',
            'access_rate_amount' => 'integer',
            'social_tariff' => 'sometimes|required',
            'components' => 'sometimes|required|array',
            'tous' => 'sometimes|required|array',
            'minimum_purchase_amount' => 'numeric',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(
            [
                'data' => [],
                'meta' => [
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors(),
                ],
            ],
            422
        );

        throw new ValidationException($validator, $response);
    }
}
