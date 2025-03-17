<?php

namespace Inensus\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserTicketCreateRequest extends FormRequest {
    public function rules(): array {
        return [];
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
