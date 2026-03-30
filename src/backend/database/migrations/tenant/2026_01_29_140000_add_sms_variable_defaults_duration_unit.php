<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $vars = [
            ['variable' => 'duration', 'value' => '30'],
            ['variable' => 'unit', 'value' => 'days'],
            ['variable' => 'meter_serial', 'value' => '47782371232'],
            ['variable' => 'device_serial', 'value' => 'SHS-001234'],
        ];
        foreach ($vars as $v) {
            $exists = DB::connection('tenant')->table('sms_variable_default_values')
                ->where('variable', $v['variable'])
                ->exists();
            if (!$exists) {
                DB::connection('tenant')->table('sms_variable_default_values')->insert([
                    'variable' => $v['variable'],
                    'value' => $v['value'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    public function down(): void {
        DB::connection('tenant')->table('sms_variable_default_values')
            ->whereIn('variable', ['duration', 'unit', 'meter_serial', 'device_serial'])
            ->delete();
    }
};
