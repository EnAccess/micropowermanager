<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Installations that restored their Airtel history keep the table they have.
        if (!Schema::connection('tenant')->hasTable('airtel_transactions')) {
            Schema::connection('tenant')->create('airtel_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('interface_id');
                $table->string('business_number');
                $table->string('trans_id');
                $table->string('tr_id')->index();
                $table->integer('status')->default(0);
                $table->string('manufacturer_transaction_type')->nullable();
                $table->integer('manufacturer_transaction_id')->nullable();
                $table->timestamps();
            });
        }

        Schema::connection('tenant')->create('airtel_credentials', static function (Blueprint $table) {
            $table->increments('id');
            $table->text('jwt_secret')->nullable();
            $table->string('phone_prefix')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('airtel_credentials');
        Schema::connection('tenant')->dropIfExists('airtel_transactions');
    }
};
