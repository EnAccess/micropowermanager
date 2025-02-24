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
        Schema::connection('tenant')->create('sms_bodies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference', 50)->unique();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->string('place_holder');
            $table->string('variables');
            $table->timestamps();
        });

        DB::connection('tenant')->table('sms_bodies')->insert(
            [
                [
                    'reference' => 'SmsTransactionHeader',
                    'place_holder' => 'Dear [name] [surname], we received your transaction [transaction_amount].',
                    'body' => 'Dear [name] [surname], we received your transaction [transaction_amount].',
                    'variables' => 'name,surname,transaction_amount',
                    'title' => 'Sms Header',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'SmsReminderHeader',
                    'place_holder' => 'Dear [name] [surname],',
                    'body' => 'Dear [name] [surname],',
                    'variables' => 'name,surname',
                    'title' => 'Sms Header',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'SmsResendInformationHeader',
                    'place_holder' => 'Dear [name] [surname], we received your resend last transaction information demand.',
                    'body' => 'Dear [name] [surname], we received your resend last transaction information demand.',
                    'variables' => 'name,surname',
                    'title' => 'Sms Header',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'EnergyConfirmation',
                    'place_holder' => 'Meter: [meter] , [token]  Unit [energy] .',
                    'body' => 'Meter: [meter] , [token]  Unit [energy] .',
                    'variables' => 'meter,token,energy',
                    'title' => 'Meter Charge',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'AccessRateConfirmation',
                    'place_holder' => 'Service Charge: [amount] ',
                    'body' => 'Service Charge: [amount] ',
                    'variables' => 'amount',
                    'title' => 'Tariff Fixed Cost',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'AssetRateReminder',
                    'place_holder' => 'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
                    'body' => 'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
                    'variables' => 'appliance_type_name,remaining,due_date',
                    'title' => 'Appliance Payment Reminder',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'AssetRatePayment',
                    'place_holder' => 'Appliance:   [appliance_type_name]  [amount]',
                    'body' => 'Appliance:   [appliance_type_name]  [amount]',
                    'variables' => 'appliance_type_name,amount',
                    'title' => 'Appliance Payment',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'OverdueAssetRateReminder',
                    'place_holder' => 'you forgot to pay the rate of [appliance_type_name] ( [remaining] )  on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
                    'body' => 'you forgot to pay the rate of [appliance_type_name] ( [remaining] )  on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
                    'variables' => 'appliance_type_name,remaining,due_date',
                    'title' => 'Overdue Appliance Payment Reminder',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'PricingDetails',
                    'place_holder' => 'Transaction amount is [amount], \n VAT for energy : [vat_energy] \n VAT for the other staffs : [vat_others] . ',
                    'body' => 'Transaction amount is [amount], \n VAT for energy : [vat_energy] \n VAT for the other staffs : [vat_others] . ',
                    'variables' => 'amount,vat_energy,vat_others',
                    'title' => 'Pricing Details',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'ResendInformation',
                    'place_holder' => 'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
                    'body' => 'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
                    'variables' => 'meter,token,energy,amount',
                    'title' => 'Resend Last Transaction Information',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'ResendInformationLastTransactionNotFound',
                    'place_holder' => 'Last transaction information not found for Meter: [meter]',
                    'body' => 'Last transaction information not found for Meter: [meter]',
                    'variables' => 'meter',
                    'title' => 'Last Transaction Information Not Found',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'SmsReminderFooter',
                    'place_holder' => 'Your Company etc.',
                    'body' => 'Your Company etc.',
                    'variables' => '',
                    'title' => 'Sms Footer',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'SmsTransactionFooter',
                    'place_holder' => 'Your Company etc.',
                    'body' => 'Your Company etc.',
                    'variables' => '',
                    'title' => 'Sms Footer',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'reference' => 'SmsResendInformationFooter',
                    'place_holder' => 'Your Company etc.',
                    'body' => 'Your Company etc.',
                    'variables' => '',
                    'title' => 'Sms Footer',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('sms_bodies');
    }
};
