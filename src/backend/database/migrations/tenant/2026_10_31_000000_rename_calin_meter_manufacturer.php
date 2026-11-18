<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->table('manufacturers')
            ->where('api_name', 'CalinMeterApi')
            ->update([
                'name' => 'Calin Meter (Legacy Token API)',
                'updated_at' => Carbon::now(),
            ]);
    }

    public function down(): void {
        DB::connection('tenant')->table('manufacturers')
            ->where('api_name', 'CalinMeterApi')
            ->update([
                'name' => 'Calin Meters',
                'updated_at' => Carbon::now(),
            ]);
    }
};
