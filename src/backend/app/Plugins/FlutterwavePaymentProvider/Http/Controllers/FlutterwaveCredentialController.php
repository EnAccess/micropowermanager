<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Http\Controllers;

use App\Models\MpmPlugin;
use App\Plugins\FlutterwavePaymentProvider\Http\Resources\FlutterwaveCredentialResource;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCompanyHashService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCredentialService;
use App\Services\MpmPluginService;
use App\Services\RegistrationTailService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

#[Group('Plugins / Flutterwave')]
class FlutterwaveCredentialController extends Controller {
    public function __construct(
        private FlutterwaveCredentialService $credentialService,
        private FlutterwaveCompanyHashService $hashService,
        private RegistrationTailService $registrationTailService,
        private MpmPluginService $mpmPluginService,
    ) {}

    public function show(): FlutterwaveCredentialResource {
        $credential = $this->credentialService->getCredentials();

        return FlutterwaveCredentialResource::make($credential);
    }

    public function update(Request $request): FlutterwaveCredentialResource {
        // The four secret fields are write-only: the resource never returns the
        // stored values, so the form re-submits them blank unless the operator
        // actually retypes them. They're nullable here for that reason — the
        // service treats a blank field as "keep the existing value" and throws
        // if the credential ends up without a required value after the merge
        // (i.e. on first save).
        $request->validate([
            'secret_key' => ['nullable', 'string', 'min:3'],
            'public_key' => ['nullable', 'string', 'min:3'],
            'encryption_key' => ['nullable', 'string', 'min:3'],
            'webhook_secret_hash' => ['nullable', 'string', 'min:3'],
            'callback_url' => ['required', 'url'],
            'merchant_name' => ['required', 'string', 'min:2'],
            'merchant_email' => ['required', 'email'],
            'environment' => ['required', 'in:test,live'],
        ]);

        $updateData = [
            'callback_url' => $request->input('callback_url'),
            'merchant_name' => $request->input('merchant_name'),
            'merchant_email' => $request->input('merchant_email'),
            'environment' => $request->input('environment'),
        ];
        if ($request->filled('secret_key')) {
            $updateData['secret_key'] = $request->input('secret_key');
        }
        if ($request->filled('public_key')) {
            $updateData['public_key'] = $request->input('public_key');
        }
        if ($request->filled('encryption_key')) {
            $updateData['encryption_key'] = $request->input('encryption_key');
        }
        if ($request->filled('webhook_secret_hash')) {
            $updateData['webhook_secret_hash'] = $request->input('webhook_secret_hash');
        }

        try {
            $credential = $this->credentialService->updateCredentials($updateData);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['secret_key' => $exception->getMessage()]);
        }

        // Mark Flutterwave step as adjusted in Registration Tail (credentials fully provided)
        try {
            $mpmPlugin = $this->mpmPluginService->getById(MpmPlugin::FLUTTERWAVE_PAYMENT_PROVIDER);
            $this->registrationTailService->adjustStep($mpmPlugin->name);
        } catch (\Throwable) {
            // Fail silently; tail update should not block credential updates
        }

        return FlutterwaveCredentialResource::make($credential);
    }

    /**
     * @return JsonResponse
     */
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
            'webhook_url' => $this->buildWebhookUrl($companyId),
            'company_id' => $companyId,
        ]);
    }

    /**
     * The URL operators must register in their Flutterwave dashboard so Flutterwave
     * can deliver charge events. Unlike the payment callback URL this points at the
     * backend.
     */
    private function buildWebhookUrl(int $companyId): string {
        $appUrl = rtrim((string) config('app.url'), '/');

        return $appUrl.'/api/flutterwave/webhook/'.$companyId;
    }

    /**
     * @return JsonResponse
     */
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
