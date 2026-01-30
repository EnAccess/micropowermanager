<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $references = [
            [
                'reference' => 'TokenConfirmationMeter',
                'place_holder' => 'Dear [name] [surname], your transaction is confirmed. Meter Serial: [meter_serial]. Token: [token], Energy: [energy] kWh.',
                'body' => 'Dear [name] [surname], your transaction is confirmed. Meter Serial: [meter_serial]. Token: [token], Energy: [energy] kWh.',
                'variables' => 'name,surname,token,energy,meter_serial',
                'title' => 'Meter token confirmation',
            ],
            [
                'reference' => 'TokenConfirmationSHS',
                'place_holder' => 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit].',
                'body' => 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit].',
                'variables' => 'name,surname,token,duration,unit,device_serial',
                'title' => 'SHS token confirmation',
            ],
            [
                'reference' => 'TransactionConfirmationNoToken',
                'place_holder' => 'Dear [name] [surname], your transaction of [amount] is confirmed.',
                'body' => 'Dear [name] [surname], your transaction of [amount] is confirmed.',
                'variables' => 'name,surname,amount',
                'title' => 'Transaction confirmation (no token)',
            ],
        ];

        foreach ($references as $ref) {
            DB::connection('tenant')->table('sms_bodies')->insert([
                'reference' => $ref['reference'],
                'place_holder' => $ref['place_holder'],
                'body' => $ref['body'],
                'variables' => $ref['variables'],
                'title' => $ref['title'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    public function down(): void {
        foreach (['TokenConfirmationMeter', 'TokenConfirmationSHS', 'TransactionConfirmationNoToken'] as $ref) {
            DB::connection('tenant')->table('sms_bodies')->where('reference', $ref)->delete();
        }
    }
};
