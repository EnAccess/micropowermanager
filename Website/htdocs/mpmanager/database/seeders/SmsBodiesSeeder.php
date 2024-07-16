<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmsBodiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('shard')->table('sms_bodies')->insert([
            [
                'reference' => 'SmsTransactionHeader',
                'place_holder' => 'Dear [name] [surname], we received your transaction [transaction_amount].',
                'body' => 'Dear [name] [surname], we received your transaction [transaction_amount].',
                'variables' => 'name,surname,transaction_amount',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SmsReminderHeader',
                'place_holder' => 'Dear [name] [surname],',
                'body' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SmsResendInformationHeader',
                'place_holder' => 'Dear [name] [surname], we received your resend last transaction information demand.',
                'body' => 'Dear [name] [surname], we received your resend last transaction information demand.',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'EnergyConfirmation',
                'place_holder' => 'Meter: [meter] , [token]  Unit [energy] .',
                'body' => 'Meter: [meter] , [token]  Unit [energy] .',
                'variables' => 'meter,token,energy',
                'title' => 'Meter Charge',
            ],
            [
                'reference' => 'AccessRateConfirmation',
                'place_holder' => 'Service Charge: [amount] ',
                'body' => 'Service Charge: [amount] ',
                'variables' => 'amount',
                'title' => 'Tariff Fixed Cost',
            ],
            [
                'reference' => 'AssetRateReminder',
                'place_holder' => 'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
                'body' => 'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
                'variables' => 'appliance_type_name,remaining,due_date',
                'title' => 'Appliance Payment Reminder',
            ],
            [
                'reference' => 'AssetRatePayment',
                'place_holder' => 'Appliance:   [appliance_type_name]  [amount]',
                'body' => 'Appliance:   [appliance_type_name]  [amount]',
                'variables' => 'appliance_type_name,amount',
                'title' => 'Appliance Payment',
            ],
            [
                'reference' => 'OverdueAssetRateReminder',
                'place_holder' => 'you forgot to pay the rate of [appliance_type_name] ( [remaining] )  on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
                'body' => 'you forgot to pay the rate of [appliance_type_name] ( [remaining] )  on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
                'variables' => 'appliance_type_name,remaining,due_date',
                'title' => 'Overdue Appliance Payment Reminder',
            ],
            [
                'reference' => 'PricingDetails',
                'place_holder' => 'Transaction amount is [amount], \n VAT for energy : [vat_energy] \n VAT for the other staffs : [vat_others] . ',
                'body' => 'Transaction amount is [amount], \n VAT for energy : [vat_energy] \n VAT for the other staffs : [vat_others] . ',
                'variables' => 'amount,vat_energy,vat_others',
                'title' => 'Pricing Details',
            ],
            [
                'reference' => 'ResendInformation',
                'place_holder' => 'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
                'body' => 'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
                'variables' => 'meter,token,energy,amount',
                'title' => 'Resend Last Transaction Information',
            ],
            [
                'reference' => 'ResendInformationLastTransactionNotFound',
                'place_holder' => 'Last transaction information not found for Meter: [meter]',
                'body' => 'Last transaction information not found for Meter: [meter]',
                'variables' => 'meter',
                'title' => 'Last Transaction Information Not Found',
            ],
            [
                'reference' => 'SmsReminderFooter',
                'place_holder' => 'Your Company etc.',
                'body' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SmsTransactionFooter',
                'place_holder' => 'Your Company etc.',
                'body' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SmsResendInformationFooter',
                'place_holder' => 'Your Company etc.',
                'body' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ]
        );
    }
}
