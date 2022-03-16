<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
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
            'name' => ['required',Rule::unique('meter_tariffs')->ignore($this->id)],
            'price' => 'required|integer', // 100 times of original price to support 2 decimal numbers.
            'currency' => 'required|string|max:20',
            'factor' => 'sometimes|integer',
            'access_rate_period' => 'required_with:access_rate_amount|integer|min:1',
            'access_rate_amount' => 'required_with:access_rate_period|integer',
            'social_tariff' => 'sometimes|required',
            'social_tariff.daily_allowance' => 'required_with:social_tariff',
            'social_tariff.price' => 'required_with:social_tariff',
            'social_tariff.initial_energy_budget' => 'required_with:social_tariff',
            'social_tariff.maximum_stacked_energy' => 'required_with:social_tariff',
            'components' => 'sometimes|required|array',
            'components.*.name' => 'required_with:components',
            'components.*.price' => 'required_with:components',
            'tous' => 'sometimes|required|array',
            'tous.*.start' => 'required_with:tous',
            'tous.*.end' => 'required_with:tous',
            'tous.*.value' => 'required_with:tous',
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
