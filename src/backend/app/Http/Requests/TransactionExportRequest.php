<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionExportRequest extends FormRequest {
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

            /*
             * Filter by device type.
             *
             * @default all
             */
            'deviceType' => ['nullable', 'in:all,meter,solar_home_system,e_bike'],

            /* Filter by device serial number. Ignored for the CSV format. */
            'serial_number' => ['nullable', 'string'],

            /* Filter by tariff id. Only applied to the Excel format. */
            'tariff' => ['nullable', 'integer'],

            /*
             * Filter by transaction provider: `agent_transaction`, `cash_transaction`,
             * `third_party_transaction`, or the alias of an installed payment provider plugin.
             *
             * @default all
             */
            'provider' => ['nullable', 'string'],

            /* Filter by transaction status: `1` = approved, `-1` = rejected. */
            'status' => ['nullable', 'integer'],

            /* Include transactions created on or after this date. */
            'from' => ['nullable', 'date'],

            /* Include transactions created on or before this date. */
            'to' => ['nullable', 'date'],
        ];
    }
}
