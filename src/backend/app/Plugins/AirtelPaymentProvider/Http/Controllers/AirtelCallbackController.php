<?php

namespace App\Plugins\AirtelPaymentProvider\Http\Controllers;

use App\Plugins\AirtelPaymentProvider\Http\Middleware\VerifyAirtelToken;
use App\Plugins\AirtelPaymentProvider\Models\AirtelTransaction;
use App\Plugins\AirtelPaymentProvider\Providers\AirtelTransactionProvider;
use App\Plugins\AirtelPaymentProvider\Services\AirtelTransactionService;
use App\Plugins\AirtelPaymentProvider\Support\AirtelXml;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

#[Group('Plugins / Airtel Money', 'Endpoints Airtel calls to validate, process and enquire about a payment')]
class AirtelCallbackController extends Controller {
    public function __construct(
        private AirtelTransactionProvider $transactionProvider,
        private AirtelTransactionService $transactionService,
    ) {}

    /**
     * Validate Payment.
     *
     * Airtel asks whether a payment for a meter can be accepted, before it collects the money.
     */
    public function validation(Request $request): Response {
        try {
            $this->transactionProvider->validateRequest($request);
            $this->transactionProvider->saveTransaction();
        } catch (\Exception $exception) {
            Log::warning('Airtel validation rejected', ['message' => $exception->getMessage()]);

            return AirtelXml::response(400, $exception->getMessage());
        }

        return AirtelXml::response(200, 'Success');
    }

    /**
     * Process Payment.
     *
     * Airtel confirms that it collected the money for a validated payment.
     */
    public function process(Request $request): Response {
        $command = $this->command($request);
        $airtelTransactionId = $command['REFERENCE1'] ?? '';

        try {
            $this->transactionService->startProcessing(
                $airtelTransactionId,
                $command['REFERENCE2'] ?? '',
                $request->attributes->getInt('companyId'),
            );
        } catch (\Exception $exception) {
            Log::warning('Airtel process rejected', ['message' => $exception->getMessage()]);

            return AirtelXml::response(400, $exception->getMessage(), ['TXNID' => $airtelTransactionId]);
        }

        return AirtelXml::response(200, 'Success', ['TXNID' => $airtelTransactionId]);
    }

    /**
     * Enquire Payment.
     *
     * Airtel asks for the outcome of a payment.
     */
    public function enquiry(Request $request): Response {
        $airtelTransaction = $this->transactionService->findByAirtelTransactionId($this->command($request)['TXNID'] ?? '');

        if (!$airtelTransaction instanceof AirtelTransaction) {
            return AirtelXml::response(404, 'Transaction not found');
        }

        [$status, $message] = match ($airtelTransaction->status) {
            AirtelTransaction::STATUS_SUCCESS => [200, 'Success'],
            AirtelTransaction::STATUS_FAILED => [404, 'Failed'],
            AirtelTransaction::STATUS_VALIDATED => [404, 'Not Found'],
            default => [400, 'Pending'],
        };

        return AirtelXml::response($status, $message, ['REF' => $airtelTransaction->id]);
    }

    /**
     * @return array<string, string>
     */
    private function command(Request $request): array {
        return $request->attributes->get(VerifyAirtelToken::COMMAND_ATTRIBUTE);
    }
}
