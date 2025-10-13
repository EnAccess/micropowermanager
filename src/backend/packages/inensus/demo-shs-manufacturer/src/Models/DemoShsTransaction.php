<?php

namespace Inensus\DemoShsManufacturer\Models;

use App\Models\Transaction\BaseManufacturerTransaction;

class DemoShsTransaction extends BaseManufacturerTransaction {
    protected $table = 'demo_shs_transactions';
}
