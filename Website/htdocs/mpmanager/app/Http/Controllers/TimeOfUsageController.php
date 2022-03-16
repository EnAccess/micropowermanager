<?php

namespace App\Http\Controllers;

use App\Models\Meter\MeterTariff;
use App\Models\TimeOfUsage;
use Illuminate\Http\Request;

class TimeOfUsageController extends Controller
{
    public function destroy(TimeOfUsage $timeOfUsage)
    {
        TimeOfUsage::find($timeOfUsage->id)->delete();
        $tariff = MeterTariff::find($timeOfUsage->tariff_id);
        return $tariff->update(['updated_at' => date('Y-m-d H:i:s')]);
    }
}
