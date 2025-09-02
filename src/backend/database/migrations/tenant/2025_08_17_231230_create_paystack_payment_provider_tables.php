<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Create paystack_credentials table
        if (!Schema::connection('tenant')->hasTable('paystack_credentials')) {
            Schema::connection('tenant')->create('paystack_credentials', function (Blueprint $table) {
                $table->id();
                $table->string('secret_key');
                $table->string('public_key');
                $table->string('webhook_secret')->nullable();
                $table->string('callback_url')->nullable();
                $table->string('merchant_name')->default('Paystack');
                $table->enum('environment', ['test', 'live'])->default('test');
                $table->timestamps();

                $table->index('environment');
            });
        }

        if (!Schema::connection('tenant')->hasTable('paystack_transactions')) {
            Schema::connection('tenant')->create('paystack_transactions', function (Blueprint $table) {
                $table->id();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('NGN');
                $table->string('order_id')->unique();
                $table->string('reference_id')->unique();
                $table->integer('status')->default(0);
                $table->string('external_transaction_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->string('serial_id')->nullable();
                $table->string('device_type')->nullable();
                $table->string('paystack_reference')->nullable()->unique();
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
                $table->index('paystack_reference');
                $table->index('external_transaction_id');
                $table->index('order_id');
                $table->index('reference_id');

                if (Schema::connection('tenant')->hasTable('customers')) {
                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('paystack_transactions');
        Schema::connection('tenant')->dropIfExists('paystack_credentials');
    }
};
