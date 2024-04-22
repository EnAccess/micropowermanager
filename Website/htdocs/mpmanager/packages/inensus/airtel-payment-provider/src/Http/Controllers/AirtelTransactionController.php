<?php
namespace Inensus\AirtelPaymentProvider\Http\Controllers;

use App\Jobs\ProcessPayment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\AirtelPaymentProvider\Services\AirtelTransactionService;
use SimpleXMLElement;

class AirtelTransactionController extends  Controller
{
    public function __construct(private AirtelTransactionService $airtelTransactionService)
    {
    }

    public function validation()
    {
        $xmlResponse =
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<COMMAND>' .
            '<STATUS>200</STATUS>' .
            '<MESSAGE>Success</MESSAGE>' .
            '</COMMAND>';

        echo $xmlResponse;
    }

    public function enquiry(Request $request)
    {
        try {
            $transactionXml = new SimpleXMLElement($request->getContent());
            $transactionData = json_encode($transactionXml);
            $transactionData = json_decode($transactionData, true);
            $trId = $transactionData['TXNID'];
            $airtelTransaction = $this->airtelTransactionService->getByTrId($trId);
            $responseMessage =
                $airtelTransaction->status == 1 ? 'Success' : ($airtelTransaction->status == -1 ? 'Failed' : 'Pending');

            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>200</STATUS>' .
                '<MESSAGE>' . $responseMessage . '</MESSAGE>' .
                '<REF>' . $airtelTransaction->id . '</REF>' .
                '</COMMAND>';

            echo $xmlResponse;
        } catch (\Exception $exception) {
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>400</STATUS>' .
                '<MESSAGE>' . $exception->getMessage() . '</MESSAGE>' .
                '</COMMAND>';

            echo $xmlResponse;
        }


    }

    public function process(Request $request)
    {
        try {
            $transactionXml = new SimpleXMLElement($request->getContent());
            $transactionData = json_encode($transactionXml);
            $transactionData = json_decode($transactionData, true);
            $trId = $transactionData['REFERENCE1'];
            $transId = $transactionData['REFERENCE2'];

            $airtelTransaction = $this->airtelTransactionService->getByTrId($trId);
            $airtelTransaction->trans_id = $transId;
            $airtelTransaction->save();
            $transactionProvider = resolve('AirtelV2PaymentProvider');
            $transactionProvider->init($airtelTransaction);

            ProcessPayment::dispatch($transactionProvider->getTransaction()->id)
                ->allOnConnection('redis')
                ->onQueue(config('services.queues.payment'));

            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>200</STATUS>' .
                '<MESSAGE>Success</MESSAGE>' .
                '<TXNID>' . $trId . '</TXNID>' .
                '</COMMAND>';

            echo $xmlResponse;
        } catch (\Exception $exception) {
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>400</STATUS>' .
                '<MESSAGE>' . $exception->getMessage() . '</MESSAGE>' .
                '<TXNID>' . $trId . '</TXNID>' .
                '</COMMAND>';

            echo $xmlResponse;
        }
    }

    public function test(Request $request)
    {
        try {

            $trId = $request->input('trId');
            $transId = $request->input('transId');

            $airtelTransaction = $this->airtelTransactionService->getByTrId($trId);
            $airtelTransaction->trans_id = $transId;
            $airtelTransaction->save();
            $transactionProvider = resolve('AirtelPaymentProvider');
            $transactionProvider->init($airtelTransaction);

            ProcessPayment::dispatch($transactionProvider->getTransaction()->id)
                ->allOnConnection('redis')
                ->onQueue(config('services.queues.payment'));

            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>200</STATUS>' .
                '<MESSAGE>Success</MESSAGE>' .
                '<TXNID>' . $trId . '</TXNID>' .
                '</COMMAND>';

            echo $xmlResponse;
        } catch (\Exception $exception) {
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>400</STATUS>' .
                '<MESSAGE>' . $exception->getMessage() . '</MESSAGE>' .
                '<TXNID>' . $trId . '</TXNID>' .
                '</COMMAND>';

            echo $xmlResponse;
        }
    }
}