<?php

use App\Models\Manufacturer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        // Updating through the model records the current name in previous_names.
        Manufacturer::query()
            ->where('api_name', 'CalinMeterApi')
            ->each(fn (Manufacturer $manufacturer) => $manufacturer->update(['name' => 'Calin (Legacy Token API)']));
    }

    public function down(): void {
        Manufacturer::query()
            ->where('api_name', 'CalinMeterApi')
            ->each(function (Manufacturer $manufacturer): void {
                $previousNames = $manufacturer->previous_names ?? [];
                $restoredName = array_pop($previousNames) ?? 'Calin Meters';

                $manufacturer->newQuery()->whereKey($manufacturer->id)->update([
                    'name' => $restoredName,
                    'previous_names' => $previousNames === [] ? null : json_encode($previousNames),
                ]);
            });
    }
};
