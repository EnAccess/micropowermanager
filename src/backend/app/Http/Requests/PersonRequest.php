<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            /*
             * The title of the person.
             *
             * @example Dr.
             */
            'title' => ['sometimes', 'nullable', 'string'],
            /*
             * @example John
             */
            'name' => ['required', 'min:2'],
            /*
             * @example Doe
             */
            'surname' => ['required', 'min:2'],
            /*
             * @example 1970-01-01
             */
            'birth_date' => ['sometimes', 'nullable', 'date'],
            /*
             * @example male
             */
            'gender' => ['sometimes', 'nullable', 'string'],
            /*
             * @example University
             */
            'education' => ['sometimes', 'nullable', 'string'],
            'city_id' => ['sometimes', 'integer', 'exists:tenant.cities,id'],
            /*
             * @example Some Street 1/13
             */
            'street' => ['sometimes', 'nullable', 'string', 'min:5'],
            /*
             * @example john.doe@mail.com
             */
            'email' => ['sometimes', 'nullable', 'email'],
            /*
             * @example +1111
             */
            'phone' => ['sometimes', 'min:11'],
            /*
             * @example NG
             */
            'country_code' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
