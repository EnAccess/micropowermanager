<?php

use App\Models\Transaction\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->table('payment_histories')
            ->where('payment_type', 'down payment')
            ->update(['payment_type' => Transaction::TYPE_DOWN_PAYMENT]);
    }

    public function down(): void {
        DB::connection('tenant')->table('payment_histories')
            ->where('payment_type', Transaction::TYPE_DOWN_PAYMENT)
            ->update(['payment_type' => 'down payment']);
    }
};
