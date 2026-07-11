<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonExportRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            /*
             * Export format.
             *
             * @default excel
             */
            'format' => ['nullable', 'in:excel,csv,json'],

            /* Filter by mini-grid name. */
            'miniGrid' => ['nullable', 'string'],

            /* Filter by village name. */
            'village' => ['nullable', 'string'],

            /* Filter by device type. */
            'deviceType' => ['nullable', 'in:meter,solar_home_system,e_bike'],

            /* Filter by activity: `true` = customers with a payment within the last 25 days, `false` = customers without one. */
            'isActive' => ['nullable', 'in:true,false'],
        ];
    }
}
