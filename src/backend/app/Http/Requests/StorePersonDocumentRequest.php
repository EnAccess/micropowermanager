<?php

namespace App\Http\Requests;

use App\Models\Person\Person;
use App\Services\PersonDocumentUploadService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StorePersonDocumentRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:5120'],
            'type' => ['required', 'string', 'max:255'],
            'additional_json' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void {
        $validator->after(function (Validator $validator): void {
            $personId = $this->route('personId');
            $person = Person::query()->find($personId);

            if ($person === null) {
                return;
            }

            $existingCount = $person->uploadedDocuments()->count();
            if ($existingCount >= PersonDocumentUploadService::MAX_DOCUMENTS_PER_PERSON) {
                throw ValidationException::withMessages(['file' => __('Maximum of :max documents reached for this customer.', ['max' => PersonDocumentUploadService::MAX_DOCUMENTS_PER_PERSON])]);
            }
        });
    }
}
