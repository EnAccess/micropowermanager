<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::connection('tenant')->create('safaricom_settings', function (Blueprint $table) {
            $table->id();
            $table->string('consumer_key');
            $table->string('consumer_secret');
            $table->string('passkey');
            $table->string('shortcode');
            $table->string('environment')->default('sandbox');
            $table->string('validation_url')->nullable();
            $table->string('confirmation_url')->nullable();
            $table->string('timeout_url')->nullable();
            $table->string('result_url')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('safaricom_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('phone_number');
            $table->string('account_reference')->nullable();
            $table->string('transaction_desc')->nullable();
            $table->string('status');
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index('reference_id');
            $table->index('status');
            $table->index('phone_number');
        });
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('safaricom_transactions');
        Schema::connection('tenant')->dropIfExists('safaricom_settings');
    }
};
