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
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging', 'API endpoints for managing WhatsApp opt-in consent of customers')]
class WhatsappContactController extends Controller {
    public function __construct(private WhatsappContactService $contactService) {}

    public function show(Person $person): JsonResponse {
        $contact = $this->contactService->findForPerson($person);

        return response()->json(['data' => $contact instanceof WhatsappContact ? WhatsappContactResource::make($this->withConsentLogs($contact)) : null]);
    }

    public function optIn(Person $person, WhatsappOptInRequest $request): WhatsappContactResource {
        $contact = $this->contactService->findOrCreateForPerson($person);
        $this->contactService->changeConsent($contact, WhatsappConsentLog::ACTION_OPT_IN, WhatsappConsentLog::SOURCE_OPERATOR, auth('api')->user()->id, $request->string('consent_note')->toString());

        return WhatsappContactResource::make($this->withConsentLogs($contact));
    }

    public function optOut(Person $person, WhatsappOptOutRequest $request): WhatsappContactResource {
        $contact = $this->contactService->findOrCreateForPerson($person);
        $note = $request->filled('consent_note') ? $request->string('consent_note')->toString() : null;
        $this->contactService->changeConsent($contact, WhatsappConsentLog::ACTION_OPT_OUT, WhatsappConsentLog::SOURCE_OPERATOR, auth('api')->user()->id, $note);

        return WhatsappContactResource::make($this->withConsentLogs($contact));
    }

    private function withConsentLogs(WhatsappContact $contact): WhatsappContact {
        return $contact->load(['consentLogs' => fn ($query) => $query->with('user:id,name')->latest('id')]);
    }
}
