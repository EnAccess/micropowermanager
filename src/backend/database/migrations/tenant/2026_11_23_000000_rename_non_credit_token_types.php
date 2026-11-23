<?php

use App\Enums\DeviceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->table('tokens')
            ->where('token_type', 'unlock')
            ->update(['token_type' => 'unlock_paygo']);

        // Meters' clear-credit tokens were stored as `reset` before they had their own type.
        DB::connection('tenant')->table('tokens')
            ->where('token_type', 'reset')
            ->whereIn('device_id', fn ($query) => $query->select('id')->from('devices')->where('device_type', DeviceType::Meter->value))
            ->update(['token_type' => 'clear_credit']);

        DB::connection('tenant')->table('tokens')
            ->where('token_type', 'reset')
            ->update(['token_type' => 'reset_credit']);
    }

    public function down(): void {
        DB::connection('tenant')->table('tokens')
            ->where('token_type', 'unlock_paygo')
            ->update(['token_type' => 'unlock']);

        DB::connection('tenant')->table('tokens')
            ->whereIn('token_type', ['reset_credit', 'clear_credit'])
            ->update(['token_type' => 'reset']);
    }
};
