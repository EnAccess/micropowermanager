<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('pesapal_credentials')) {
            Schema::connection('tenant')->create('pesapal_credentials', function (Blueprint $table) {
                $table->id();
                $table->text('consumer_key');
                $table->text('consumer_secret');
                $table->string('callback_url', 255)->nullable();
                $table->string('merchant_name')->default('Pesapal');
                $table->string('merchant_email')->nullable();
                $table->enum('environment', ['test', 'live'])->default('test');
                $table->string('currency', 3)->default('KES');
                $table->string('ipn_id')->nullable();
                $table->timestamp('ipn_registered_at')->nullable();
                $table->timestamps();

                $table->index('environment');
            });
        }

        if (!Schema::connection('tenant')->hasTable('pesapal_transactions')) {
            Schema::connection('tenant')->create('pesapal_transactions', function (Blueprint $table) {
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
                $table->string('order_tracking_id')->nullable()->unique();
                $table->string('merchant_reference')->nullable();
                $table->string('manufacturer_transaction_type')->nullable();
                $table->integer('manufacturer_transaction_id')->nullable();
                $table->text('payment_url')->nullable();
                $table->json('metadata')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamps();
                $table->index('customer_id');
                $table->index('serial_id');
                $table->index('device_type');
                $table->index('status');
                $table->index('order_tracking_id');
                $table->index('merchant_reference');
                $table->index('external_transaction_id');
                $table->index('order_id');
                $table->index('reference_id');
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('pesapal_transactions');
        Schema::connection('tenant')->dropIfExists('pesapal_credentials');
    }
};
