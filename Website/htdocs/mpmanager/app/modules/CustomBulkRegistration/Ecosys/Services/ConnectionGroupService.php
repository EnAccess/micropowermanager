<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\ConnectionGroup;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class ConnectionGroupService extends CreatorService
{
    private $group1 = 'SunKing Home 40Plus - Sofala -  Humanitário 2';
    private $group2 = 'SunKing Home 40Plus - Nacional - COVID PLUS 2';
    public function __construct(ConnectionGroup $connectionGroup)
    {
        parent::__construct($connectionGroup);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $connectionConfig = [
            'price' => 'price',
        ];
        $price = $csvData[$connectionConfig['price']];

        $connectionGroupData = [
            'name' => $price == 6450 ? $this->group1 : $this->group2
        ];
        return $this->createRelatedDataIfDoesNotExists($connectionGroupData);
    }
}
