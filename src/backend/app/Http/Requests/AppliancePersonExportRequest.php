<?php

namespace App\Http\Requests;

use App\Models\AppliancePerson;
use Illuminate\Foundation\Http\FormRequest;

class AppliancePersonExportRequest extends FormRequest {
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

            /* Filter by payment type. */
            'paymentType' => ['nullable', 'in:'.AppliancePerson::PAYMENT_TYPE_INSTALLMENT.','.AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE],

            /* Filter by the ID of the customer (person) the AppliancePerson records belong to. */
            'personId' => ['nullable', 'integer'],
        ];
    }
}
