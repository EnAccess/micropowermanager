<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\PaystackPaymentProvider\Http\Resources\PaystackCredentialResource;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;

class PaystackCredentialController extends Controller {
    public function __construct(
        private PaystackCredentialService $credentialService,
    ) {}

    public function show(): PaystackCredentialResource {
        $credential = $this->credentialService->getCredentials();

        return PaystackCredentialResource::make($credential);
    }

    public function update(Request $request): PaystackCredentialResource {
        $request->validate([
            'secret_key' => 'required|string|min:3',
            'public_key' => 'required|string|min:3',
            'webhook_secret' => 'required|string|min:3',
            'callback_url' => 'required|url',
            'merchant_name' => 'required|string|min:2',
            'environment' => 'required|in:test,live',
        ]);

        $credential = $this->credentialService->updateCredentials([
            'secret_key' => $request->input('secret_key'),
            'public_key' => $request->input('public_key'),
            'webhook_secret' => $request->input('webhook_secret'),
            'callback_url' => $request->input('callback_url'),
            'merchant_name' => $request->input('merchant_name'),
            'environment' => $request->input('environment'),
        ]);

        return PaystackCredentialResource::make($credential);
    }
}
