<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam title string optional. The title of the person. Example: Dr.
 * @bodyParam name string required. Example: John
 * @bodyParam surname string required. Example: Doe
 * @bodyParam birth_date string optional. Example: 1970-01-01
 * @bodyParam gender string optional Example: male
 * @bodyParam education string optional. Example: University
 * @bodyParam city_id int optional. Example: 1
 * @bodyParam street string optional. Example: Some Street 1/13
 * @bodyParam email string optional. Example: john.doe@mail.com
 * @bodyParam phone string optional. Example: +1111
 * @bodyParam country_code string optional. Example: NG, US, etc.
 */
class PersonRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'title' => ['sometimes', 'nullable', 'string'],
            'name' => ['required', 'min:2'],
            'surname' => ['required', 'min:2'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female'],
            'education' => ['sometimes', 'nullable', 'string'],
            'city_id' => ['sometimes', 'integer', 'exists:tenant.cities,id'],
            'street' => ['sometimes', 'nullable', 'string', 'min:5'],
            'email' => ['sometimes', 'nullable', 'email'],
            'phone' => ['sometimes', 'min:11'],
            'country_code' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
