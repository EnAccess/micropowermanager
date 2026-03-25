<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLE_NAME = 'sms_transactions';

    public function up(): void {
        if (!Schema::connection('tenant')->hasTable(self::TABLE_NAME)) {
            Schema::connection('tenant')->create(self::TABLE_NAME, function (Blueprint $table) {
                $table->increments('id');
                $table->string('provider_name');
                $table->string('transaction_reference')->unique();
                $table->double('amount');
                $table->string('sender_phone');
                $table->string('device_serial')->nullable();
                $table->text('raw_message');
                $table->integer('status')->default(0);
                $table->string('manufacturer_transaction_type')->nullable();
                $table->integer('manufacturer_transaction_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists(self::TABLE_NAME);
    }
};
