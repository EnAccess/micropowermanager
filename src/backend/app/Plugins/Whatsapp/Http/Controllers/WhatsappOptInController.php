<?php

namespace App\Plugins\Whatsapp\Http\Controllers;

use App\Plugins\Whatsapp\Models\WhatsappOptIn;
use App\Plugins\Whatsapp\Services\WhatsappOptInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WhatsappOptInController extends Controller {
    public function __construct(
        private WhatsappOptInService $optInService,
        private WhatsappOptIn $optIn,
    ) {}

    public function index(): JsonResponse {
        return response()->json($this->optIn->newQuery()->orderByDesc('id')->paginate(50));
    }

    public function store(Request $request): JsonResponse {
        $request->validate(['phone' => ['required', 'string']]);

        return response()->json($this->optInService->optIn($request->input('phone')), 201);
    }

    /**
     * Revoking keeps the row, so there is a record of when consent was withdrawn.
     */
    public function destroy(int $id): JsonResponse {
        $record = $this->optIn->newQuery()->findOrFail($id);
        $record->update(['revoked_at' => now()]);

        return response()->json($record);
    }
}
