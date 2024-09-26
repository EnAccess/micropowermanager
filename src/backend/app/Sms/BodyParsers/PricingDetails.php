<?php

namespace App\Sms\BodyParsers;

use App\Models\MainSettings;
use App\Models\Transaction\Transaction;

class PricingDetails extends SmsBodyParser
{
    public $variables = ['amount', 'vat_energy', 'vat_others'];
    protected $transaction;
    private $vatEnergy = 0;
    private $vatOtherStaffs = 0;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->calculateTaxes();
    }

    protected function getVariableValue($variable)
    {
        switch ($variable) {
            case 'amount':
                $variable = $this->transaction->amount;
                break;
            case 'vat_energy':
                $variable = $this->vatEnergy;
                break;
            case 'vat_others':
                $variable = $this->vatOtherStaffs;
                break;
        }

        return $variable;
    }

    private function calculateTaxes()
    {
        $mainSettings = MainSettings::query()->first();
        $energy = $this->transaction->paymentHistories->where('payment_type', 'energy')->sum('amount');
        $other = $this->transaction->paymentHistories->where('payment_type', '!=', 'energy')->sum('amount');
        $this->vatEnergy = $energy * $mainSettings->vat_energy / 100;
        $this->vatOtherStaffs = $other * $mainSettings->vat_appliance / 100;
    }
}
