<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('kelin_api_credentials')) {
            Schema::connection('tenant')->create('kelin_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->nullable();
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->string('authentication_token')->nullable();
                $table->boolean('is_authenticated')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_customers')) {
            Schema::connection('tenant')->create('kelin_customers', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('mpm_customer_id')->unique(); // Code of the customer, id of corresponding person in MPM
                $table->string('customer_no')->unique();
                $table->string('address');
                $table->string('mobile');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_meters')) {
            Schema::connection('tenant')->create('kelin_meters', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('mpm_meter_id')->unique();
                $table->string('meter_address')->unique();
                $table->string('meter_name');
                $table->string('customer_no');
                $table->integer('rtuId');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_transactions')) {
            Schema::connection('tenant')->create('kelin_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('meter_serial');
                $table->decimal('amount');
                $table->integer('op_type');
                $table->string('pay_kwh');
                $table->string('open_token_1');
                $table->string('open_token_2');
                $table->string('pay_token');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_sync_actions')) {
            Schema::connection('tenant')->create('kelin_sync_actions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sync_setting_id');
                $table->integer('attempts')->default(0);
                $table->dateTime('last_sync')->nullable();
                $table->dateTime('next_sync')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_sync_settings')) {
            Schema::connection('tenant')->create('kelin_sync_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('action_name')->unique();
                $table->string('sync_in_value_str');
                $table->integer('sync_in_value_num');
                $table->integer('max_attempts')->default(3);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_meter_minutely_datas')) {
            Schema::connection('tenant')->create('kelin_meter_minutely_datas', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_of_terminal');
                $table->integer('id_of_measurement_point');
                $table->string('address_of_meter');
                $table->string('name_of_meter');
                $table->integer('date_of_data');
                $table->integer('time_of_data');
                $table->decimal('positive_active_value');
                $table->decimal('positive_reactive_value');
                $table->decimal('inverted_active_value');
                $table->decimal('inverted_reactive_value');
                $table->decimal('positive_active_minute');
                $table->decimal('positive_reactive_minute');
                $table->decimal('inverted_active_minute');
                $table->decimal('inverted_reactive_minute');
                $table->decimal('voltage_of_phase_a');
                $table->decimal('voltage_of_phase_b');
                $table->decimal('voltage_of_phase_c');
                $table->decimal('power');
                $table->decimal('power_factor');
                $table->decimal('reactive_power');
                $table->decimal('current_of_phase_a');
                $table->decimal('current_of_phase_b');
                $table->decimal('current_of_phase_c');
                $table->decimal('temperature_1');
                $table->decimal('temperature_2');
                $table->decimal('pressure_1');
                $table->decimal('pressure_2');
                $table->decimal('flow_velocity');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_meter_daily_datas')) {
            Schema::connection('tenant')->create('kelin_meter_daily_datas', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_of_terminal');
                $table->integer('id_of_measurement_point');
                $table->string('address_of_meter');
                $table->string('name_of_meter');
                $table->integer('date_of_data');
                $table->decimal('total_positive_active_power_cumulative_flow_indication');
                $table->decimal('total_positive_active_peak_power');
                $table->decimal('total_positive_active_flat_power');
                $table->decimal('total_positive_active_valley_power');
                $table->decimal('total_positive_active_spike_power');
                $table->decimal('total_positive_reactive_power_cumulative_flow_indication');
                $table->decimal('total_positive_reactive_peak_power');
                $table->decimal('total_positive_reactive_flat_power');
                $table->decimal('total_positive_reactive_valley_power');
                $table->decimal('total_positive_reactive_spike_power');
                $table->decimal('total_reverted_active_power_cumulative_flow_indication');
                $table->decimal('total_reverted_reactive_power_cumulative_flow_indication');
                $table->decimal('positive_active_total_daily_power');
                $table->decimal('positive_active_daily_power_in_peak');
                $table->decimal('positive_active_daily_power_in_flat');
                $table->decimal('positive_active_daily_power_in_valley');
                $table->decimal('positive_active_daily_power_in_spike');
                $table->decimal('positive_reactive_total_daily_power');
                $table->decimal('reverted_active_total_daily_power');
                $table->decimal('reverted_reactive_total_daily_power');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kelin_settings')) {
            Schema::connection('tenant')->create('kelin_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('setting_id');
                $table->string('setting_type');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('kelin_api_credentials');
        Schema::connection('tenant')->dropIfExists('kelin_customers');
        Schema::connection('tenant')->dropIfExists('kelin_meters');
        Schema::connection('tenant')->dropIfExists('kelin_sync_actions');
        Schema::connection('tenant')->dropIfExists('kelin_sync_settings');
        Schema::connection('tenant')->dropIfExists('kelin_settings');
        Schema::connection('tenant')->dropIfExists('kelin_meter_minutely_datas');
        Schema::connection('tenant')->dropIfExists('kelin_meter_daily_datas');
        Schema::connection('tenant')->dropIfExists('kelin_transactions');
    }
};
