<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {

        if (!Schema:: hasTable('sm_api_credentials')) {
            Schema::create('sm_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('https://www.sparkmeter.cloud/api/v0');
                $table->string('api_key')->nullable();
                $table->string('api_secret')->nullable();
                $table->boolean('is_authenticated')->default(0);

                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_customers')) {
            Schema::create('sm_customers', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('customer_id')->unique(); // Code of the customer, id of corresponding person in MPM
                $table->string('site_id');
                $table->integer('mpm_customer_id')->unique(); // Code of the customer, id of corresponding person in MPM
                $table->decimal('credit_balance');
                $table->decimal('low_balance_limit');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_meter_models')) {
            Schema::create('sm_meter_models', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('site_id');
                $table->string('model_name'); // Meter model name that uses as starting word on creating customer
                $table->integer('mpm_meter_type_id');
                $table->integer('continuous_limit');
                $table->integer('inrush_limit');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_organizations')) {
            Schema::create('sm_organizations', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('organization_id')->unique();
                $table->string('code')->unique();
                $table->string('display_name');
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sites')) {
            Schema::create('sm_sites', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('site_id')->unique();
                $table->integer('mpm_mini_grid_id')->unique();
                $table->string('thundercloud_url')->unique();
                $table->string('thundercloud_token')->nullable();
                $table->boolean('is_authenticated')->default(0);
                $table->boolean('is_online')->default(0);
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_tariffs')) {
            Schema::create('sm_tariffs', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('site_id');
                $table->string('tariff_id')->unique();
                $table->integer('mpm_tariff_id')->unique(); // Tariff ID corresponding tariff id in MPM
                $table->integer('flat_load_limit');
                $table->string('plan_duration')->nullable();
                $table->integer('plan_price')->nullable();
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_transactions')) {
            Schema::create('sm_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('site_id');
                $table->string('customer_id');
                $table->string('transaction_id')->unique();
                $table->integer('external_id')->nullable();
                $table->string('status')->default('created');
                $table->string('timestamp');
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sync_actions')) {
            Schema::create('sm_sync_actions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sync_setting_id');
                $table->integer('attempts')->default(0);
                $table->dateTime('last_sync')->nullable();
                $table->dateTime('next_sync')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_settings')) {
            Schema::create('sm_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('setting_id');
                $table->string('setting_type');
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sms_settings')) {
            Schema::create('sm_sms_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('state')->unique();
                $table->integer('not_send_elder_than_mins');
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sync_settings')) {
            Schema::create('sm_sync_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('action_name')->unique();
                $table->string('sync_in_value_str');
                $table->integer('sync_in_value_num');
                $table->integer('max_attempts')->default(3);
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sms_notified_customers')) {
            Schema::create('sm_sms_notified_customers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('customer_id');
                $table->string('notify_type');
                $table->string('notify_id')->nullable()->unique();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sms_bodies')) {
            Schema::create('sm_sms_bodies', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference', 50)->unique();
                $table->string('title')->nullable();
                $table->string('body')->nullable();
                $table->string('place_holder');
                $table->string('variables');
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sms_variable_default_values')) {
            Schema::create('sm_sms_variable_default_values', function (Blueprint $table) {
                $table->id();
                $table->string('variable');
                $table->string('value');
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sales_accounts')) {
            Schema::create('sm_sales_accounts', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('sales_account_id');
                $table->string('site_id');
                $table->string('name');
                $table->string('account_type'); // Meter model name that uses as starting word on creating customer
                $table->boolean('active');
                $table->decimal('credit')->nullable();
                $table->decimal('markup')->nullable();
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('sm_sms_feedback_words')) {
            Schema::create('sm_sms_feedback_words', function (Blueprint $table) {
                $table->increments('id');
                $table->string('meter_reset')->nullable();
                $table->string('meter_balance')->nullable();
                $table->timestamps();
            });
        }

    }

    public function down()
    {
        Schema::dropIfExists('sm_api_credentials');
        Schema::dropIfExists('sm_customers');
        Schema::dropIfExists('sm_meter_models');
        Schema::dropIfExists('sm_organizations');
        Schema::dropIfExists('sm_sites');
        Schema::dropIfExists('sm_tariffs');
        Schema::dropIfExists('sm_transactions');
        Schema::dropIfExists('sm_sync_actions');
        Schema::dropIfExists('sm_settings');
        Schema::dropIfExists('sm_sms_settings');
        Schema::dropIfExists('sm_sync_settings');
        Schema::dropIfExists('sm_sms_notified_customers');
        Schema::dropIfExists('sm_sms_bodies');
        Schema::dropIfExists('sm_sms_variable_default_values');
        Schema::dropIfExists('sm_sales_accounts');
        Schema::dropIfExists('sm_sms_feedback_words');
    }
};