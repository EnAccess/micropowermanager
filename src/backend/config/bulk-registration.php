<?php

return [
    'csv_fields' => [
        'person' => [
            'name' => 'First Name',
            'm-name' => 'Middle Name',
            'surname' => 'Surname',
            'sex' => 'Gender',
            'birth_date' => 'Date of birth',
        ],

        'cluster' => [
            'name' => 'Cluster Name',
        ],

        'mini_grid' => [
            'cluster_id' => 'cluster_id',
            'name' => 'Mini Grid Name',
        ],

        'city' => [
            'cluster_id' => 'cluster_id',
            'mini_grid_id' => 'mini_grid_id',
            'name' => 'Village Name',
        ],

        'address' => [
            'person_id' => 'person_id',
            'city_id' => 'city_id',
            'phone' => 'Phone number',
            'alternative_phone' => 'Alternate phone number',
        ],

        'tariff' => [
            'name' => 'Tariff Name',
            'currency' => 'Currency',
            'price' => 'Tariff Price',
            'total_price' => 'Tariff Price',
        ],

        'connection_type' => [
            'name' => 'Connection Type',
        ],

        'connection_group' => [
            'name' => 'Connection Group',
        ],

        'appliance_type' => [
            'name' => 'What appliance would you like to purchase?',
            'price' => 0,
        ],

        'manufacturer' => [
            'name' => 'Meter Manufacturer Name',
        ],

        'meter' => [
            'serial_number' => 'Meter Serial Number',
            'in_use' => 1,
            'manufacturer_id' => 'manufacturer_id',
        ],

        'meter_parameter' => [
            'owner_type' => 'person',
            'owner_id' => 'person_id',
            'meter_id' => 'meter_id',
            'connection_type_id' => 'connection_type_id',
            'connection_group_id' => 'connection_group_id',
            'tariff_id' => 'tariff_id',
        ],

        'geographical_information' => [
            'owner_type' => 'owner_type',
            'owner_id' => 'owner_id',
            'points' => 'points',
            'household_latitude' => '_GPS location of household_latitude',
            'household_longitude' => '_GPS location of household_longitude',
            'household' => 'GPS location of household',
        ],

        'person_docs' => [
            'customer_picture' => [
                'person_id' => 'person_id',
                'name' => 'name',
                'type' => 'Customer Picture',
                'location' => null,
            ],
            'signed_contract' => [
                'person_id' => 'person_id',
                'name' => 'name',
                'type' => 'Take picture of signed contract',
                'location' => null,
            ],
            'customer_id' => [
                'person_id' => 'person_id',
                'name' => 'name',
                'type' => 'Take picture of customer ID',
                'location' => null,
            ],
            'payment receipt' => [
                'person_id' => 'person_id',
                'name' => 'name',
                'type' => 'Take picture of customer payment reciept',
                'location' => null,
            ],
        ],
    ],
    'appliance_types' => ['TV - 24', 'Option 5', 'Fridge', 'Freezer', 'Fan'],

    'geocoder' => [
        'key' => 'AIzaSyAnSY-zdlCXxLwW9jgmbVEo_fwLMSDkG9E',
        'country' => 'NG',
    ],

    'reflections' => [
        'PersonService' => 'Inensus\BulkRegistration\Services\PersonService',
        'PersonDocumentService' => 'Inensus\BulkRegistration\Services\PersonDocumentService',
        'ClusterService' => 'Inensus\BulkRegistration\Services\ClusterService',
        'MiniGridService' => 'Inensus\BulkRegistration\Services\MiniGridService',
        'GeographicalInformationService' => 'Inensus\BulkRegistration\Services\GeographicalInformationService',
        'CityService' => 'Inensus\BulkRegistration\Services\CityService',
        'AddressService' => 'Inensus\BulkRegistration\Services\AddressService',
        'TariffService' => 'Inensus\BulkRegistration\Services\TariffService',
        'ConnectionTypeService' => 'Inensus\BulkRegistration\Services\ConnectionTypeService',
        'ConnectionGroupService' => 'Inensus\BulkRegistration\Services\ConnectionGroupService',
        'ApplianceTypeService' => 'Inensus\BulkRegistration\Services\ApplianceTypeService',
        'MeterService' => 'Inensus\BulkRegistration\Services\MeterService',
        'ManufacturerService' => 'Inensus\BulkRegistration\Services\ManufacturerService',
    ],
];
