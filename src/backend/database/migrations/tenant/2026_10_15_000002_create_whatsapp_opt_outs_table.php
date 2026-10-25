<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_opt_outs')) {
            Schema::connection('tenant')->create('whatsapp_opt_outs', static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('phone', 32)->index();
                $table->unsignedInteger('person_id')->nullable()->index();
                $table->string('preferred_channel', 16)->default('sms');
                $table->boolean('opted_out')->default(true);
                $table->string('reason', 64)->nullable();
                $table->timestamp('opted_out_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_opt_outs');
    }
};
