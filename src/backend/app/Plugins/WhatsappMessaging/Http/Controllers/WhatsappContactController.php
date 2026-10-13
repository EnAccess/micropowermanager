<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Models\Person\Person;
use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappOptInRequest;
use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappOptOutRequest;
use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappContactResource;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Services\WhatsappContactService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging', 'API endpoints for managing WhatsApp opt-in consent of customers')]
class WhatsappContactController extends Controller {
    public function __construct(private WhatsappContactService $contactService) {}

    public function index(Request $request): AnonymousResourceCollection {
        return WhatsappContactResource::collection(WhatsappContact::query()
            ->with('person')
            ->when($request->filled('status'), fn ($query) => $query->where('opt_in_status', $request->string('status')->toString()))
            ->latest()
            ->paginate());
    }

    public function optIn(Person $person, WhatsappOptInRequest $request): WhatsappContactResource {
        $phone = $request->filled('phone') ? phone($request->string('phone')->toString())->formatE164() : null;
        $contact = $this->contactService->findOrCreateForPerson($person, $phone);
        $this->contactService->changeConsent($contact, WhatsappConsentLog::ACTION_OPT_IN, WhatsappConsentLog::SOURCE_OPERATOR, auth('api')->user()->id, $request->string('consent_note')->toString());

        return WhatsappContactResource::make($contact->load('person'));
    }

    public function optOut(Person $person, WhatsappOptOutRequest $request): WhatsappContactResource {
        $contact = $this->contactService->findOrCreateForPerson($person, null);
        $note = $request->filled('consent_note') ? $request->string('consent_note')->toString() : null;
        $this->contactService->changeConsent($contact, WhatsappConsentLog::ACTION_OPT_OUT, WhatsappConsentLog::SOURCE_OPERATOR, auth('api')->user()->id, $note);

        return WhatsappContactResource::make($contact->load('person'));
    }

    public function consentLogs(WhatsappContact $contact): JsonResponse {
        return response()->json($contact->consentLogs()->latest()->paginate());
    }
}
