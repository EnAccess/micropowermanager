<?php

namespace App\Plugins\WhatsappMessaging\Http\Requests;

use App\Plugins\WhatsappMessaging\Services\WhatsappMessageLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WhatsappSavingsRequest extends FormRequest {
    // Bounds the series so one request never builds more than a year of buckets.
    public const int MAX_RANGE_DAYS = 366;

    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group_by' => ['nullable', Rule::in(WhatsappMessageLogService::SAVINGS_GROUPINGS)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $days = Carbon::parse($this->string('from')->toString())->diffInDays(Carbon::parse($this->string('to')->toString())) + 1;
                if ($days > self::MAX_RANGE_DAYS) {
                    $validator->errors()->add('to', sprintf('The period may cover at most %d days.', self::MAX_RANGE_DAYS));
                }
            },
        ];
    }

    public function groupBy(): string {
        return $this->filled('group_by') ? $this->string('group_by')->toString() : WhatsappMessageLogService::GROUP_BY_DAY;
    }
}
