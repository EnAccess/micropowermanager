<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SmsVariableDefaultValuesSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sms_variable_default_values')->insert(array(
                [
                    'variable' => 'name',
                    'value' => 'Herbert',
                ],
                [
                    'variable' => 'surname',
                    'value' => 'Kale',
                ],
                [
                    'variable' => 'amount',
                    'value' => '1000',
                ],
                [
                    'variable' => 'appliance_type_name',
                    'value' => 'fridge',
                ],
                [
                    'variable' => 'remaining',
                    'value' => '3',
                ],
                [
                    'variable' => 'due_date',
                    'value' => '2021/04/01',
                ],
                [
                    'variable' => 'meter',
                    'value' => '47782371232',
                ],
                [
                    'variable' => 'token',
                    'value' => '5111 3511 9911 1177 7711',
                ],
                [
                    'variable' => 'vat_energy',
                    'value' => '15',
                ],
                [
                    'variable' => 'vat_others',
                    'value' => '10',
                ],
                [
                    'variable' => 'energy',
                    'value' => '5123.1',
                ],[
                    'variable' => 'transaction_amount',
                    'value' => '500',
                ],
            )
        );
    }
}
