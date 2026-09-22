<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // The Android Gateway sentinel ID (2) was never a real MpmPlugin row - it happened to
        // collide with MpmPlugin::STEAMACO_METER. Clear it so no company is left pointing at a
        // gateway that no longer resolves to anything; they'll see "no gateway configured" and
        // need to pick a real provider (AfricasTalking, TextBee, or Viber).
        DB::connection('tenant')->table('main_settings')->where('sms_gateway_id', 2)->update(['sms_gateway_id' => null]);

        Schema::connection('tenant')->dropIfExists('sms_android_settings');
    }

    public function down(): void {
        Schema::connection('tenant')->create('sms_android_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->default('https://fcm.googleapis.com/fcm/send');
            $table->string('token')->nullable();
            $table->string('key')->nullable();
            $table->string('callback')->default('http://localhost:8000/api/sms/%s/confirm');
            $table->timestamps();
        });
    }
};
