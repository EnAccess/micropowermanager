<?php

namespace App\Plugins\OdysseyDataExport\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OdysseyPaymentsRequest extends FormRequest {
    private const int MAX_RANGE_HOURS = 24;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'FROM' => ['required', 'date'],
            'TO' => ['required', 'date', 'after_or_equal:FROM'],
            'SITE_ID' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'FROM' => 'FROM',
            'TO' => 'TO',
            'SITE_ID' => 'SITE_ID',
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                if ($this->fromDate()->diffInHours($this->toDate()) > self::MAX_RANGE_HOURS) {
                    $validator->errors()->add('TO', 'The range between FROM and TO must be <= '.self::MAX_RANGE_HOURS.' hours.');
                }
            },
        ];
    }

    // The Odyssey Standard Payment API expects failures in its own response shape.
    protected function failedValidation(Validator $validator): void {
        throw new HttpResponseException(response()->json(['payments' => [], 'errors' => implode(' ', $validator->errors()->all())], 400));
    }

    public function fromDate(): CarbonImmutable {
        return CarbonImmutable::parse($this->string('FROM')->toString());
    }

    public function toDate(): CarbonImmutable {
        return CarbonImmutable::parse($this->string('TO')->toString());
    }

    public function siteId(): ?string {
        return $this->filled('SITE_ID') ? $this->string('SITE_ID')->toString() : null;
    }
}
