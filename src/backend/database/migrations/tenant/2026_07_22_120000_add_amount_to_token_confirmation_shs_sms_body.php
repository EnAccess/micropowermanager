<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const BODY_WITH_AMOUNT = 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit]. Amount: [amount].';
    private const BODY_WITHOUT_AMOUNT = 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit].';

    public function up(): void {
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'TokenConfirmationSHS')
            ->update([
                'body' => self::BODY_WITH_AMOUNT,
                'place_holder' => self::BODY_WITH_AMOUNT,
                'variables' => 'name,surname,token,duration,unit,device_serial,amount',
                'updated_at' => Carbon::now(),
            ]);
    }

    public function down(): void {
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'TokenConfirmationSHS')
            ->update([
                'body' => self::BODY_WITHOUT_AMOUNT,
                'place_holder' => self::BODY_WITHOUT_AMOUNT,
                'variables' => 'name,surname,token,duration,unit,device_serial',
                'updated_at' => Carbon::now(),
            ]);
    }
};
