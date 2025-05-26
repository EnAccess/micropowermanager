<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Move data from meter_tokens to tokens
        $meterTokens = DB::connection('tenant')->table('meter_tokens')->get();

        foreach ($meterTokens as $meterToken) {
            // Get the device_id from the meter
            $device = DB::connection('tenant')
                ->table('devices')
                ->where('device_type', 'meter')
                ->where('device_id', $meterToken->meter_id)
                ->first();

            if ($device) {
                // Insert into tokens table
                DB::connection('tenant')->table('tokens')->upsert([
                    [
                        'transaction_id' => $meterToken->transaction_id,
                        'device_id' => $device->id,
                        'token' => $meterToken->token,
                        'token_amount' => $meterToken->energy,
                        'token_unit' => 'kWh',
                        'token_type' => 'energy',
                        'created_at' => $meterToken->created_at,
                        'updated_at' => $meterToken->updated_at,
                    ],
                ], ['transaction_id']);
            }
        }

        // Drop the meter_tokens table
        Schema::connection('tenant')->dropIfExists('meter_tokens');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('meter_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id')->unique();
            $table->integer('meter_id');
            $table->string('token');
            $table->double('energy');
            $table->timestamps();
        });
    }
};
