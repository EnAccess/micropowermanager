<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class CreateTargetRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'data' => 'required',
            'period' => 'required',
            'targetForType' => 'required|string',
            'targetForId' => 'required|numeric|min:1',
        ];
    }

    public function getTargetForType(): string {
        return $this->input('targetForType');
    }

    public function getTargetForId(): int {
        return $this->input('targetForId');
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array {
        return $this->input('data');
    }

    public function getPeriod(): CarbonImmutable {
        return CarbonImmutable::parse($this->input('period'));
    }
}
