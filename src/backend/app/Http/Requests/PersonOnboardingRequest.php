<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonOnboardingRequest extends FormRequest {
    public const MAX_ANSWERS = 50;

    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'answers' => ['present', 'array', 'list', 'max:'.self::MAX_ANSWERS],
            /*
             * @example Household size
             */
            'answers.*.question' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            /*
             * @example 6
             */
            'answers.*.answer' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Collapses the submitted rows into the question-keyed map stored in
     * `people.onboarding_json`. Row order becomes key order, which is the order
     * the customer detail page renders.
     *
     * @return array<string, string|null>
     */
    public function answers(): array {
        /** @var list<array{question: string, answer?: string|null}> $rows */
        $rows = $this->validated()['answers'];
        $answers = [];

        foreach ($rows as $row) {
            $answers[$row['question']] = $row['answer'] ?? null;
        }

        return $answers;
    }
}
