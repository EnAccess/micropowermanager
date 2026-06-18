<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Controllers;

use App\Models\MpmPlugin;
use App\Plugins\PesapalPaymentProvider\Http\Resources\PesapalCredentialResource;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCompanyHashService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Services\MpmPluginService;
use App\Services\RegistrationTailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class PesapalCredentialController extends Controller {
    public function __construct(
        private PesapalCredentialService $credentialService,
        private PesapalCompanyHashService $hashService,
        private RegistrationTailService $registrationTailService,
        private MpmPluginService $mpmPluginService,
    ) {}

    public function show(): PesapalCredentialResource {
        $credential = $this->credentialService->getCredentials();

        return PesapalCredentialResource::make($credential);
    }

    public function update(Request $request): PesapalCredentialResource {
        $supportedCurrencies = config('pesapal-payment-provider.currency.supported', ['KES', 'UGX', 'TZS', 'USD']);

        // consumer_key and consumer_secret are optional on update: the resource
        // never returns the stored values, so the form re-submits them blank
        // unless the operator actually retypes them. The service treats blank
        // as "keep the existing value" and errors if the credential ends up
        // without keys after the merge (i.e. first save).
        $request->validate([
            'consumer_key' => ['nullable', 'string', 'min:3'],
            'consumer_secret' => ['nullable', 'string', 'min:3'],
            'callback_url' => ['required', 'url'],
            'merchant_name' => ['required', 'string', 'min:2'],
            'merchant_email' => ['required', 'email'],
            'environment' => ['required', 'in:test,live'],
            'currency' => ['required', 'string', 'in:'.implode(',', $supportedCurrencies)],
        ]);

        $updateData = [
            'callback_url' => $request->input('callback_url'),
            'merchant_name' => $request->input('merchant_name'),
            'merchant_email' => $request->input('merchant_email'),
            'environment' => $request->input('environment'),
            'currency' => $request->input('currency'),
        ];
        if ($request->filled('consumer_key')) {
            $updateData['consumer_key'] = $request->input('consumer_key');
        }
        if ($request->filled('consumer_secret')) {
            $updateData['consumer_secret'] = $request->input('consumer_secret');
        }

        try {
            $credential = $this->credentialService->updateCredentials($updateData);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['consumer_key' => $exception->getMessage()]);
        }

        try {
            $registrationTail = $this->registrationTailService->getFirst();
            $tailArray = empty($registrationTail->tail) ? [] : json_decode($registrationTail->tail, true);

            $mpmPlugin = $this->mpmPluginService->getById(MpmPlugin::PESAPAL_PAYMENT_PROVIDER);
            $pesapalTag = $mpmPlugin->name;

            $updated = false;
            foreach ($tailArray as &$item) {
                if (isset($item['tag']) && $item['tag'] === $pesapalTag) {
                    $item['adjusted'] = true;
                    $updated = true;
                    break;
                }
            }
            unset($item);

            if ($updated) {
                $this->registrationTailService->update($registrationTail, ['tail' => json_encode($tailArray)]);
            }
        } catch (\Throwable) {
            // Tail update is non-blocking.
        }

        return PesapalCredentialResource::make($credential);
    }

    public function generatePublicUrls(Request $request): JsonResponse {
        $companyId = $request->attributes->get('companyId');

        $permanentPaymentUrl = $this->hashService->generatePermanentPaymentUrl($companyId);
        $timeBasedPaymentUrl = $this->hashService->generatePublicUrl($companyId, 'payment');
        $timeBasedResultUrl = $this->hashService->generatePublicUrl($companyId, 'result');

        return response()->json([
            'permanent_payment_url' => $permanentPaymentUrl,
            'time_based_payment_url' => $timeBasedPaymentUrl,
            'time_based_result_url' => $timeBasedResultUrl,
            'company_id' => $companyId,
        ]);
    }

    public function generateAgentPaymentUrl(Request $request): JsonResponse {
        $companyId = $request->attributes->get('companyId');
        $customerId = $request->input('customer_id');
        $agentId = $request->input('agent_id');

        $agentPaymentUrl = $this->hashService->generateAgentPaymentUrl($companyId, $customerId, $agentId);

        return response()->json([
            'agent_payment_url' => $agentPaymentUrl,
            'expires_in_hours' => 24,
            'customer_id' => $customerId,
            'agent_id' => $agentId,
        ]);
    }
}
