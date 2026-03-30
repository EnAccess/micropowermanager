<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentPerformanceMetricsRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'period' => ['nullable', 'in:daily,weekly,monthly,yearly'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidatedData(): array {
        $data = $this->validated();

        $data['start_date'] ??= null;
        $data['end_date'] ??= null;
        $data['period'] ??= 'monthly';

        return $data;
    }
}
