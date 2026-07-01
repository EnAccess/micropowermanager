<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('safaricom_credentials')) {
            Schema::connection('tenant')->create('safaricom_credentials', function (Blueprint $table) {
                $table->id();
                $table->text('consumer_key')->nullable();
                $table->text('consumer_secret')->nullable();
                $table->text('passkey')->nullable();
                $table->string('shortcode')->nullable();
                $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
                $table->string('validation_url')->nullable();
                $table->string('confirmation_url')->nullable();
                $table->string('timeout_url')->nullable();
                $table->string('result_url')->nullable();
                $table->timestamps();

                $table->index('environment');
            });
        }

        if (!Schema::connection('tenant')->hasTable('safaricom_transactions')) {
            Schema::connection('tenant')->create('safaricom_transactions', function (Blueprint $table) {
                $table->id();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('KES');
                $table->string('order_id')->unique();
                $table->string('reference_id')->unique();
                $table->integer('status')->default(0);
                $table->string('external_transaction_id')->nullable();
                $table->integer('customer_id');
                $table->string('serial_id')->nullable();
                $table->string('device_type')->nullable();
                $table->string('phone_number');
                $table->string('checkout_request_id')->nullable();
                $table->string('merchant_request_id')->nullable();
                $table->string('mpesa_receipt_number')->nullable();
                $table->timestamp('transaction_date')->nullable();
                $table->string('account_reference')->nullable();
                $table->string('transaction_desc')->nullable();
                $table->string('manufacturer_transaction_type')->nullable();
                $table->integer('manufacturer_transaction_id')->nullable();
                $table->json('response_data')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamps();

                $table->index('customer_id');
                $table->index('serial_id');
                $table->index('device_type');
                $table->index('status');
                $table->index('phone_number');
                $table->index('checkout_request_id');
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('safaricom_transactions');
        Schema::connection('tenant')->dropIfExists('safaricom_credentials');
    }
};
