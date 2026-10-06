<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserTicketCreateRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'owner_id' => ['required', 'integer', 'exists:tenant.people,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'label' => ['required', 'integer'],
            'assignedPerson' => ['nullable', 'integer'],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    public function getTitle(): string {
        return $this->string('title')->toString();
    }

    public function getOwnerId(): int {
        return $this->integer('owner_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function getMappedArray(): array {
        return [
            'title' => $this->getTitle(),
            'assigned_id' => $this->getAssignedPerson(),
            'due_date' => $this->getDueDate(),
            'content' => $this->getDescription(),
            'category_id' => $this->getLabel(),
        ];
    }

    public function getLabel(): int {
        return $this->integer('label');
    }

    public function getAssignedPerson(): ?int {
        return $this->filled('assignedPerson') ? $this->integer('assignedPerson') : null;
    }

    public function getDescription(): string {
        return $this->string('description')->toString();
    }

    public function getDueDate(): ?string {
        return $this->filled('dueDate') ? $this->string('dueDate')->toString() : null;
    }
}
