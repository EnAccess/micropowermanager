<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('steama_agents')) {
            Schema::connection('tenant')->create('steama_agents', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('site_id');
                $table->integer('agent_id')->unique();
                $table->integer('mpm_agent_id')->unique();
                $table->boolean('is_credit_limited');
                $table->decimal('credit_balance');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_asset_rates_payment_plans')) {
            Schema::connection('tenant')->create('steama_asset_rates_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('mpm_asset_people_id');
                $table->decimal('down_payment');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_credentials')) {
            Schema::connection('tenant')->create('steama_credentials', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->boolean('is_authenticated')->default(false);
                $table->string('api_url')->default('https://api.steama.co');
                $table->string('authentication_token')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_customer_basis_payment_plans')) {
            Schema::connection('tenant')->create('steama_customer_basis_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('customer_id');
                $table->string('payment_plan_type');
                $table->integer('payment_plan_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_customer_basis_time_of_usages')) {
            Schema::connection('tenant')->create('steama_customer_basis_time_of_usages', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('start');
                $table->integer('end');
                $table->decimal('value');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_customers')) {
            Schema::connection('tenant')->create('steama_customers', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('site_id');
                $table->integer('user_type_id');
                $table->integer('customer_id')->unique();
                $table->integer('mpm_customer_id')->unique();
                $table->decimal('energy_price')->default(0);
                $table->decimal('account_balance')->default(0);
                $table->decimal('low_balance_warning')->default(0);
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_flat_rate_payment_plans')) {
            Schema::connection('tenant')->create('steama_flat_rate_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('energy_price')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_hybrid_payment_plans')) {
            Schema::connection('tenant')->create('steama_hybrid_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('connection_fee')->nullable();
                $table->decimal('subscription_cost')->default(0);
                $table->string('payment_days_of_month');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_meter_types')) {
            Schema::connection('tenant')->create('steama_meter_types', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('mpm_meter_type_id');
                $table->string('version');
                $table->string('usage_spike_threshold');
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_meters')) {
            Schema::connection('tenant')->create('steama_meters', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('meter_id')->unique();
                $table->integer('customer_id');
                $table->integer('bit_harvester_id')->nullable()->default(0);
                $table->integer('mpm_meter_id')->unique();
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_minimum_top_up_requirements_payment_plans')) {
            Schema::connection('tenant')->create('steama_minimum_top_up_requirements_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('threshold')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_per_kwh_payment_plans')) {
            Schema::connection('tenant')->create('steama_per_kwh_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('energy_price')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_site_level_payment_plan_types')) {
            Schema::connection('tenant')->create('steama_site_level_payment_plan_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_site_level_payment_plans')) {
            Schema::connection('tenant')->create('steama_site_level_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('site_id');
                $table->integer('payment_plan_type_id');
                $table->integer('start');
                $table->integer('end');
                $table->decimal('value');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sites')) {
            Schema::connection('tenant')->create('steama_sites', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('site_id')->unique();
                $table->integer('mpm_mini_grid_id')->unique();
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_subscription_payment_plans')) {
            Schema::connection('tenant')->create('steama_subscription_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('plan_fee')->default(0);
                $table->string('plan_duration')->default(0);
                $table->decimal('energy_allotment')->nullable();
                $table->boolean('top_ups_enabled')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_tariff_override_payment_plans')) {
            Schema::connection('tenant')->create('steama_tariff_override_payment_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->decimal('energy_price')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_tariffs')) {
            Schema::connection('tenant')->create('steama_tariffs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('mpm_tariff_id')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_transactions')) {
            Schema::connection('tenant')->create('steama_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('site_id');
                $table->integer('transaction_id')->unique();
                $table->integer('customer_id');
                $table->decimal('amount');
                $table->string('category');
                $table->string('provider');
                $table->timestamp('timestamp');
                $table->string('synchronization_status');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_user_types')) {
            Schema::connection('tenant')->create('steama_user_types', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('mpm_connection_type_id');
                $table->string('name');
                $table->string('syntax');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sync_actions')) {
            Schema::connection('tenant')->create('steama_sync_actions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sync_setting_id');
                $table->integer('attempts')->default(0);
                $table->dateTime('last_sync')->nullable();
                $table->dateTime('next_sync')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_settings')) {
            Schema::connection('tenant')->create('steama_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('setting_id');
                $table->string('setting_type');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sms_settings')) {
            Schema::connection('tenant')->create('steama_sms_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('state')->unique();
                $table->integer('not_send_elder_than_mins');
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sync_settings')) {
            Schema::connection('tenant')->create('steama_sync_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('action_name')->unique();
                $table->string('sync_in_value_str');
                $table->integer('sync_in_value_num');
                $table->integer('max_attempts')->default(3);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sms_notified_customers')) {
            Schema::connection('tenant')->create('steama_sms_notified_customers', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('customer_id');
                $table->string('notify_type');
                $table->string('notify_id')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sms_bodies')) {
            Schema::connection('tenant')->create('steama_sms_bodies', function (Blueprint $table) {
                $table->increments('id');
                $table->string('reference', 50)->unique();
                $table->string('title')->nullable();
                $table->string('body')->nullable();
                $table->string('place_holder');
                $table->string('variables');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sms_variable_default_values')) {
            Schema::connection('tenant')->create('steama_sms_variable_default_values', function (Blueprint $table) {
                $table->id();
                $table->string('variable');
                $table->string('value');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('steama_sms_feedback_words')) {
            Schema::connection('tenant')->create('steama_sms_feedback_words', function (Blueprint $table) {
                $table->increments('id');
                $table->string('meter_balance')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('steama_agents');
        Schema::connection('tenant')->dropIfExists('steama_asset_rates_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_credentials');
        Schema::connection('tenant')->dropIfExists('steama_customer_basis_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_customer_basis_time_of_usages');
        Schema::connection('tenant')->dropIfExists('steama_customers');
        Schema::connection('tenant')->dropIfExists('steama_flat_rate_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_hybrid_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_meter_types');
        Schema::connection('tenant')->dropIfExists('steama_meters');
        Schema::connection('tenant')->dropIfExists('steama_minimum_top_up_requirements_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_per_kwh_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_site_level_payment_plan_types');
        Schema::connection('tenant')->dropIfExists('steama_site_level_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_sites');
        Schema::connection('tenant')->dropIfExists('steama_subscription_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_tariff_override_payment_plans');
        Schema::connection('tenant')->dropIfExists('steama_tariffs');
        Schema::connection('tenant')->dropIfExists('steama_transactions');
        Schema::connection('tenant')->dropIfExists('steama_user_types');
        Schema::connection('tenant')->dropIfExists('steama_sync_actions');
        Schema::connection('tenant')->dropIfExists('steama_settings');
        Schema::connection('tenant')->dropIfExists('steama_sms_settings');
        Schema::connection('tenant')->dropIfExists('steama_sync_settings');
        Schema::connection('tenant')->dropIfExists('steama_sms_notified_customers');
        Schema::connection('tenant')->dropIfExists('steama_sms_bodies');
        Schema::connection('tenant')->dropIfExists('steama_sms_variable_default_values');
        Schema::connection('tenant')->dropIfExists('steama_sms_feedback_words');
    }
};
