<?php


namespace Inensus\BulkRegistration\Services;


use App\Models\Meter\MeterParameter;

class MeterParameterService extends CreatorService
{

    public function __construct(MeterParameter $meterParameter)
    {
        parent::__construct($meterParameter);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $meterParameterConfig = config('bulk-registration.csv_fields.meter_parameter');
        $meterParameterData = [
            'owner_type' => $meterParameterConfig['owner_type'],
            'owner_id' => $csvData[$meterParameterConfig['owner_id']],
            'meter_id' => $csvData[$meterParameterConfig['meter_id']],
            'connection_type_id' => $csvData[$meterParameterConfig['connection_type_id']],
            'connection_group_id' => $csvData[$meterParameterConfig['connection_group_id']],
            'tariff_id' => $csvData[$meterParameterConfig['tariff_id']]
        ];
        return $this->createRelatedDataIfDoesNotExists($meterParameterData);
    }
}