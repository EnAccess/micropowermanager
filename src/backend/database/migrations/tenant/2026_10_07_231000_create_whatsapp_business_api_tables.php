<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_credentials')) {
            Schema::connection('tenant')->create('whatsapp_credentials', function (Blueprint $table) {
                $table->id();
                $table->text('access_token')->nullable();
                $table->text('phone_number_id')->nullable();
                $table->text('waba_id')->nullable();
                $table->text('webhook_secret')->nullable();
                $table->string('transaction_confirmation_template')->nullable();
                $table->string('reminder_template')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('whatsapp_business_opt_ins')) {
            Schema::connection('tenant')->create('whatsapp_business_opt_ins', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('person_id')->unique();
                $table->boolean('opted_in')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_business_opt_ins');
        Schema::connection('tenant')->dropIfExists('whatsapp_credentials');
    }
};
