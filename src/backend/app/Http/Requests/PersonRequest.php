<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam title string required. The title of the person. Example: Dr.
 * @bodyParam name string required. Example: John
 * @bodyParam surname string required. Example: Doe
 * @bodyParam birth_date string. Example: 1970-01-01
 * @bodyParam sex string required Example: male
 * @bodyParam education string. Example: University
 * @bodyParam city_id int. Example: 1
 * @bodyParam street string. Example: Some Street 1/13
 * @bodyParam email string. Example: john.doe@mail.com
 * @bodyParam phone string. Example: +1111
 * @bodyParam nationality string. Example: Earth Citizen
 */
class PersonRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'title' => 'sometimes|string',
            'name' => 'required|min:3',
            'surname' => 'required|min:3',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|min:11',
        ];
    }
}
