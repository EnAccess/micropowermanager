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
            $transactionData = json_decode(json_encode($transactionXml), true);
            $trId = $transactionData['TXNID'];
            $airtelTransaction = $this->airtelTransactionService->getByTrId($trId);

            if (!$airtelTransaction) {
                throw new \Exception('Transaction not found');
            }

            $responseMessage = $this->getResponseMessage($airtelTransaction->status);
            $status = $this->getStatusCode($responseMessage);

            $xmlResponse = $this->generateXmlResponse($status, $responseMessage, $airtelTransaction->id);
            echo $xmlResponse;
        } catch (\Exception $exception) {
            $xmlResponse = $this->generateXmlResponse(404, $exception->getMessage());
            echo $xmlResponse;
        }
    }

    private function getResponseMessage($status)
    {
        switch ($status) {
            case 1:
                return 'Success';
            case -1:
                return 'Failed';
            case 2:
                return 'Not Found';
            default:
                return 'Pending';
        }
    }

    private function getStatusCode($responseMessage)
    {
        switch ($responseMessage) {
            case 'Success':
                return 200;
            case 'Failed':
            case 'Not Found':
                return 404;
            case 'Pending':
                return 400;
            default:
                return 500;
        }
    }

    private function generateXmlResponse($status, $message, $ref = null)
    {
        $xml = new SimpleXMLElement('<COMMAND/>');
        $xml->addChild('STATUS', $status);
        $xml->addChild('MESSAGE', $message);
        if ($ref !== null) {
            $xml->addChild('REF', $ref);
        }
        return $xml->asXML();
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
            $airtelTransaction->status = 0;
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