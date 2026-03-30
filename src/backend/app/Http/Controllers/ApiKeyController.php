<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller {
    public function __construct(private ApiKey $apiKey) {}

    public function index(Request $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');

        return response()->json([
            'data' => $this->apiKey->query()->where('company_id', $companyId)->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $name = $request->input('name');

        // plaintext token returned once
        $plaintext = Str::random(40).bin2hex(random_bytes(16));
        $hash = hash('sha256', $plaintext);

        // Use the relationship to create the API key
        $company = Company::findOrFail($companyId);
        $apiKey = $company->apiKeys()->create([
            'name' => $name,
            'token_hash' => $hash,
            'active' => true,
        ]);

        return response()->json([
            'data' => [
                'id' => $apiKey->id,
                'token' => $plaintext,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $key = $this->apiKey->query()->where('company_id', $companyId)->findOrFail($id);
        $key->delete();

        return response()->json(['message' => 'revoked']);
    }
}
