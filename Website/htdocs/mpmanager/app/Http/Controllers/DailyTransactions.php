<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 02.10.18
 * Time: 10:44.
 */

namespace App\Http\Controllers;

use App\Models\Transaction\Transaction;
use Illuminate\Http\Request;

/**
 * Class DailyTransactions.
 *
 * @group Export
 */
class DailyTransactions extends Controller
{
    /**
     * The used transaction model.
     *
     * @var Transaction
     */
    private $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getDailyReport(Request $request)
    {
        $date = $request->get('date') ?? date('Y-m-d');

        $transactions = $this->transaction::with('originalTransaction')->whereDate('created_at', $date)->get();
        $transactionOutput = [];
        foreach ($transactions as $t) {
            if ($t->originalTransaction->status !== 1) { // the transaction is either not confirmed or cancelled
                continue;
            }

            $paymentHistory = $t->paymentHistories()->first();
            $payer = 'Unknown';
            if ($paymentHistory !== null) {
                $payer = $paymentHistory->payer()->first()->name.' '.$paymentHistory->payer()->first()->surname;
            }

            $transactionOutput[] = [
                'TxDate' => date('Y/m/d', strtotime($t->created_at)),
                'Description' => $t->type === 'energy' ? 'Electricity sale' : 'deferred payment',
                'Amount' => $t->amount,
                'UseTax' => 'Y',
                'TaxType' => '2',
                'TaxAccount' => '9600',
                'TaxAmount' => round(($t->amount * 100 / 118) * 0.18, 2), // 18 percent of the total
                'Project' => 'Jumeme',
                'Account' => 'P10100>P101200',
                'IsDebit' => 'N',
                'SplitType' => '0',
                'SplitGroup' => '0',
                'Reconcile' => 'N',
                'PostDated' => 'N',
                'UseDiscount' => 'N',
                'DiscPerc' => 'N',
                'DiscTrCode' => '',
                'DiscDesc' => '',
                'UseDiscTax' => 'N',
                'DiscTaxType' => '',
                'DiscTaxAcc' => '',
                'DiscTaxAmt' => 0,
                'PayeeName' => $payer,
                'PrintCheque' => 'Y',
                'SalesRep' => '',
                'Module' => '',
                'SagePayExtra1' => '',
                'SagePayExtra2' => '',
                'SagePayExtra3' => '',
            ];
        }

        return $this->downloadCSV($transactionOutput, $date.'-transactions');
    }

    private function downloadCSV(array $transactionData, string $fileName)
    {
        if (count($transactionData) === 0) { // to prevent to download an empty file
            return null;
        }
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename='.$fileName.'.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        array_unshift($transactionData, array_keys($transactionData[0]));
        $callback = function () use ($transactionData): void {
            $fh = fopen('php://output', 'wb');
            foreach ($transactionData as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
