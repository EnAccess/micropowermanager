<?php

namespace Inensus\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserTicketCreateRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'label' => ['required', 'integer'],
            'assignedPerson' => ['nullable', 'integer'],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    public function getTitle(): string {
        return $this->input('title');
    }

    public function getOwnerId(): ?int {
        return $this->input('owner_id');
    }

    public function getOutsourcing(): bool {
        return $this->input('outsourcing');
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
        return $this->input('label');
    }

    private function getAssignedPerson(): ?int {
        return $this->input('assignedPerson');
    }

    private function getDescription(): string {
        return $this->input('description');
    }

    private function getDueDate(): ?string {
        return $this->input('dueDate');
    }
}
