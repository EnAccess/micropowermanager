<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Controllers;

use App\Models\MpmPlugin;
use App\Services\MpmPluginService;
use App\Services\RegistrationTailService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\PaystackPaymentProvider\Http\Resources\PaystackCredentialResource;
use Inensus\PaystackPaymentProvider\Services\PaystackCompanyHashService;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;

class PaystackCredentialController extends Controller {
    public function __construct(
        private PaystackCredentialService $credentialService,
        private PaystackCompanyHashService $hashService,
        private RegistrationTailService $registrationTailService,
        private MpmPluginService $mpmPluginService,
    ) {}

    public function show(): PaystackCredentialResource {
        $credential = $this->credentialService->getCredentials();

        return PaystackCredentialResource::make($credential);
    }

    public function update(Request $request): PaystackCredentialResource {
        $request->validate([
            'secret_key' => 'required|string|min:3',
            'public_key' => 'required|string|min:3',
            'callback_url' => 'required|url',
            'merchant_name' => 'required|string|min:2',
            'environment' => 'required|in:test,live',
        ]);

        $credential = $this->credentialService->updateCredentials([
            'secret_key' => $request->input('secret_key'),
            'public_key' => $request->input('public_key'),
            'callback_url' => $request->input('callback_url'),
            'merchant_name' => $request->input('merchant_name'),
            'environment' => $request->input('environment'),
        ]);

        // Mark Paystack step as adjusted in Registration Tail (credentials fully provided)
        try {
            $registrationTail = $this->registrationTailService->getFirst();
            $tailArray = !empty($registrationTail->tail) ? json_decode($registrationTail->tail, true) : [];

            $mpmPlugin = $this->mpmPluginService->getById(MpmPlugin::PAYSTACK_PAYMENT_PROVIDER);
            $paystackTag = $mpmPlugin->tail_tag;

            $updated = false;
            foreach ($tailArray as &$item) {
                if (isset($item['tag']) && $item['tag'] === $paystackTag) {
                    $item['adjusted'] = true;
                    $updated = true;
                    break;
                }
            }
            unset($item);

            if ($updated) {
                $this->registrationTailService->update($registrationTail, ['tail' => json_encode($tailArray)]);
            }
        } catch (\Throwable $e) {
            // Fail silently; tail update should not block credential updates
        }

        return PaystackCredentialResource::make($credential);
    }

    public function generatePublicUrls(Request $request) {
        $companyId = $request->attributes->get('companyId');

        // Generate permanent URLs (never expire)
        $permanentPaymentUrl = $this->hashService->generatePermanentPaymentUrl($companyId);

        // Generate time-based URLs (expire in 24 hours)
        $timeBasedPaymentUrl = $this->hashService->generatePublicUrl($companyId, 'payment');
        $timeBasedResultUrl = $this->hashService->generatePublicUrl($companyId, 'result');

        return response()->json([
            'permanent_payment_url' => $permanentPaymentUrl,
            'time_based_payment_url' => $timeBasedPaymentUrl,
            'time_based_result_url' => $timeBasedResultUrl,
            'company_id' => $companyId,
        ]);
    }

    public function generateAgentPaymentUrl(Request $request) {
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
