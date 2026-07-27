<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const BODY_WITH_AMOUNT = 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit]. Amount: [amount].';
    private const BODY_WITHOUT_AMOUNT = 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit].';

    private const VARIABLES_WITH_AMOUNT = 'name,surname,token,duration,unit,device_serial,amount';
    private const VARIABLES_WITHOUT_AMOUNT = 'name,surname,token,duration,unit,device_serial';

    public function up(): void {
        // body is user-editable via Settings; only add [amount] where it still matches the seeded
        // default so we never overwrite a tenant's customized message.
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'TokenConfirmationSHS')
            ->where('body', self::BODY_WITHOUT_AMOUNT)
            ->update([
                'body' => self::BODY_WITH_AMOUNT,
                'updated_at' => Carbon::now(),
            ]);

        // place_holder (editor hint text) and variables (insertable placeholder chips) are display-only
        // metadata the backend never reads, so refresh them for every tenant — this offers [amount] as an
        // insertable placeholder even where the body was customized.
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'TokenConfirmationSHS')
            ->update([
                'place_holder' => self::BODY_WITH_AMOUNT,
                'variables' => self::VARIABLES_WITH_AMOUNT,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function down(): void {
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'TokenConfirmationSHS')
            ->where('body', self::BODY_WITH_AMOUNT)
            ->update([
                'body' => self::BODY_WITHOUT_AMOUNT,
                'updated_at' => Carbon::now(),
            ]);

        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'TokenConfirmationSHS')
            ->update([
                'place_holder' => self::BODY_WITHOUT_AMOUNT,
                'variables' => self::VARIABLES_WITHOUT_AMOUNT,
                'updated_at' => Carbon::now(),
            ]);
    }
};
