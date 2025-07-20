<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentPerformanceMetricsRequest extends FormRequest {
    /**
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:weekly,monthly',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function getValidatedData(): array {
        $data = $this->validated();

        $data['start_date'] = $data['start_date'] ?? null;
        $data['end_date'] = $data['end_date'] ?? null;
        $data['period'] = $data['period'] ?? 'monthly';

        return $data;
    }
}
