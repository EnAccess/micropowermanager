<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonDocumentRequest;
use App\Http\Requests\UpdatePersonDocumentRequest;
use App\Http\Resources\ApiResource;
use App\Models\PersonDocument;
use App\Services\AgentCustomerService;
use App\Services\AgentService;
use App\Services\PersonDocumentUploadService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentCustomerDocumentController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AgentCustomerService $agentCustomerService,
        private PersonDocumentUploadService $uploadService,
    ) {}

    public function index(int $customerId): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $person = $this->agentCustomerService->findForAgent($agent, $customerId);

        return ApiResource::make($person->uploadedDocuments()->latest()->get());
    }

    public function store(int $customerId, StorePersonDocumentRequest $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $person = $this->agentCustomerService->findForAgent($agent, $customerId);

        /** @var array<string, mixed>|null $additional */
        $additional = $request->input('additional_json');

        $document = $this->uploadService->upload(
            $person,
            $request->file('file'),
            $request->string('type')->toString(),
            $additional,
        );

        return ApiResource::make($document);
    }

    public function update(PersonDocument $personDocument, UpdatePersonDocumentRequest $request): ApiResource {
        $this->ensureAgentOwnsDocument($personDocument);

        /** @var array<string, mixed> $additional */
        $additional = $request->input('additional_json');

        $document = $this->uploadService->updateAdditional($personDocument, $additional ?: null);

        return ApiResource::make($document);
    }

    public function show(PersonDocument $personDocument): StreamedResponse {
        $this->ensureAgentOwnsDocument($personDocument);

        return $this->uploadService->download($personDocument);
    }

    public function destroy(PersonDocument $personDocument): ApiResource {
        $this->ensureAgentOwnsDocument($personDocument);

        $this->uploadService->delete($personDocument);

        return ApiResource::make(['message' => 'deleted']);
    }

    private function ensureAgentOwnsDocument(PersonDocument $personDocument): void {
        $agent = $this->agentService->getByAuthenticatedUser();

        try {
            $this->agentCustomerService->findForAgent($agent, $personDocument->person_id);
        } catch (ModelNotFoundException) {
            throw new AuthorizationException();
        }
    }
}
