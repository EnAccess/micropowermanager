<?php

namespace App\Plugins\SparkShs\Http\Controllers;

use App\Plugins\SparkShs\Http\Requests\SparkShsCredentialRequest;
use App\Plugins\SparkShs\Http\Resources\SparkShsResource;
use App\Plugins\SparkShs\Services\SparkShsCredentialService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class SparkShsCredentialController extends Controller {
    public function __construct(
        private SparkShsCredentialService $credentialService,
    ) {}

    public function show(): SparkShsResource {
        return SparkShsResource::make($this->credentialService->getCredentials());
    }

    public function update(SparkShsCredentialRequest $request): SparkShsResource {
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        $credentials = $this->credentialService->updateCredentials([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        return SparkShsResource::make($credentials);
    }

    public function check(SparkShsCredentialRequest $request): JsonResponse {
        $urls = [
            'auth_url' => $request->input('auth_url'),
            'api_url' => $request->input('api_url'),
        ];

        foreach ($urls as $name => $url) {
            $parts = parse_url($url);

            if (($parts['scheme'] ?? null) !== 'https') {
                abort(403, "Only HTTPS URLs allowed for {$name}");
            }

            if (empty($parts['host'])) {
                abort(403, "Invalid URL for {$name}");
            }

            $ip = gethostbyname($parts['host']);

            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                abort(403, "Invalid target address for {$name}");
            }
        }

        try {
            $response = Http::asJson()
                ->post($urls['auth_url'], [
                    'client_id' => $request->input('client_id'),
                    'client_secret' => $request->input('client_secret'),
                    'audience' => $urls['api_url'],
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException) {
            abort(503, 'Service unavailable');
        }

        if ($response->successful()) {
            return response()->json([
                'valid' => true,
            ]);
        }

        if (in_array($response->status(), [400, 401])) {
            return response()->json([
                'valid' => false,
            ]);
        }

        abort(502, 'Authentication service error. Are URLs correct?');
    }
}
