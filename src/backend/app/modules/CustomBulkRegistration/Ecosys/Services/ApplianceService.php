<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\Asset;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class ApplianceService extends CreatorService {
    private $appliance1 = 'SunKing Home 40Plus - Sofala -  Humanitário 2';
    private $appliance2 = 'SunKing Home 40Plus - Nacional - COVID PLUS 2';

    public function __construct(Asset $asset) {
        parent::__construct($asset);
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $applianceConfig = [
            'price' => 'price',
        ];
        $price = $csvData[$applianceConfig['price']];

        $applianceData = [
            'price' => $price,
            'asset_type_id' => 1,
            'name' => $price == 6450 ? $this->appliance1 : $this->appliance2,
        ];

        return $this->createRelatedDataIfDoesNotExists($applianceData);
    }
}
