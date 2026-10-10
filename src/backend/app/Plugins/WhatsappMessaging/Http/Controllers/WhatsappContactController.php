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
    public function __construct(
        private WhatsappContactService $contactService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection {
        $status = $request->filled('status') ? $request->string('status')->toString() : null;

        return WhatsappContactResource::collection($this->contactService->getPaginated($status));
    }

    public function optIn(Person $person, WhatsappOptInRequest $request): WhatsappContactResource {
        $phone = $request->filled('phone') ? phone($request->string('phone')->toString())->formatE164() : null;
        $contact = $this->contactService->findOrCreateForPerson($person, $phone);

        $this->contactService->optIn(
            $contact,
            WhatsappConsentLog::SOURCE_OPERATOR,
            auth('api')->user()->id,
            $request->string('consent_note')->toString(),
        );

        return WhatsappContactResource::make($contact->load('person'));
    }

    public function optOut(Person $person, WhatsappOptOutRequest $request): WhatsappContactResource {
        $contact = $this->contactService->findOrCreateForPerson($person, null);

        $this->contactService->optOut(
            $contact,
            WhatsappConsentLog::SOURCE_OPERATOR,
            auth('api')->user()->id,
            $request->filled('consent_note') ? $request->string('consent_note')->toString() : null,
        );

        return WhatsappContactResource::make($contact->load('person'));
    }

    public function consentLogs(WhatsappContact $contact): JsonResponse {
        return response()->json($this->contactService->getConsentLogs($contact));
    }
}
