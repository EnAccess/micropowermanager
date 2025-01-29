<?php

use Carbon\Carbon;
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
        Schema::connection('tenant')->create('sms_variable_default_values', function (Blueprint $table) {
            $table->id();
            $table->string('variable');
            $table->string('value');
            $table->timestamps();
        });

        DB::connection('tenant')->table('sms_variable_default_values')->insert([
            [
                'variable' => 'name',
                'value' => 'Herbert',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'surname',
                'value' => 'Kale',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'amount',
                'value' => '1000',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'appliance_type_name',
                'value' => 'fridge',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'remaining',
                'value' => '3',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'due_date',
                'value' => '2021/04/01',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'meter',
                'value' => '47782371232',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'token',
                'value' => '5111 3511 9911 1177 7711',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'vat_energy',
                'value' => '15',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'vat_others',
                'value' => '10',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'energy',
                'value' => '5123.1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'variable' => 'transaction_amount',
                'value' => '500',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('sms_variable_default_values');
    }
};
