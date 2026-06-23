<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomMobileMoney\Http\Controllers;

use App\Plugins\SafaricomMobileMoney\Http\Resources\SafaricomCredentialResource;
use App\Plugins\SafaricomMobileMoney\Services\SafaricomCredentialService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class SafaricomCredentialController extends Controller {
    public function __construct(
        private SafaricomCredentialService $credentialService,
    ) {}

    public function show(): SafaricomCredentialResource {
        return SafaricomCredentialResource::make($this->credentialService->getCredentials());
    }

    public function update(Request $request): SafaricomCredentialResource {
        $request->validate([
            'consumer_key' => ['nullable', 'string', 'min:3'],
            'consumer_secret' => ['nullable', 'string', 'min:3'],
            'passkey' => ['nullable', 'string', 'min:3', 'required_if:environment,production'],
            'shortcode' => ['nullable', 'string', 'min:3', 'max:20', 'required_if:environment,production'],
            'environment' => ['required', 'in:sandbox,production'],
            'validation_url' => ['nullable', 'url'],
            'confirmation_url' => ['nullable', 'url'],
            'timeout_url' => ['nullable', 'url'],
            'result_url' => ['nullable', 'url'],
        ]);

        $updateData = [
            'shortcode' => $request->input('shortcode'),
            'environment' => $request->input('environment'),
            'validation_url' => $request->input('validation_url'),
            'confirmation_url' => $request->input('confirmation_url'),
            'timeout_url' => $request->input('timeout_url'),
            'result_url' => $request->input('result_url'),
        ];
        foreach (['consumer_key', 'consumer_secret', 'passkey'] as $field) {
            if ($request->filled($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        try {
            $credential = $this->credentialService->updateCredentials($updateData);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['consumer_key' => $exception->getMessage()]);
        }

        return SafaricomCredentialResource::make($credential);
    }
}
