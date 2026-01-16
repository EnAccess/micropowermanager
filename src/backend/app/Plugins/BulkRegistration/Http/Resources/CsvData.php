<?php

namespace App\Plugins\BulkRegistration\Http\Resources;

use App\Plugins\BulkRegistration\Models\CsvData as CsvDataModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CsvDataModel
 */
class CsvData extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request) {
        return [
            'data' => [
                'type' => 'csv_data',
                'csv_data_id' => $this->id,
                'attributes' => [
                    'created_person_id' => $this->user_id,
                    'csv_filename' => $this->csv_filename,
                    'recently_created_records' => $this->recently_created_records ?? '',
                    'alert' => $this->alert ?? '',
                ],
            ],
        ];
    }
}
