<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonDocumentRequest;
use App\Http\Requests\UpdatePersonDocumentRequest;
use App\Http\Resources\ApiResource;
use App\Models\PersonDocument;
use App\Services\PersonDocumentUploadService;
use App\Services\PersonService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonDocumentController extends Controller {
    public function __construct(
        private PersonService $personService,
        private PersonDocumentUploadService $uploadService,
    ) {}

    public function index(int $personId): ApiResource {
        $person = $this->personService->getById($personId);

        return ApiResource::make($person->uploadedDocuments()->latest()->get());
    }

    public function store(int $personId, StorePersonDocumentRequest $request): ApiResource {
        $person = $this->personService->getById($personId);

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
        /** @var array<string, mixed> $additional */
        $additional = $request->input('additional_json');

        $document = $this->uploadService->updateAdditional($personDocument, $additional ?: null);

        return ApiResource::make($document);
    }

    public function show(PersonDocument $personDocument): StreamedResponse {
        return $this->uploadService->download($personDocument);
    }

    public function destroy(PersonDocument $personDocument): ApiResource {
        $this->uploadService->delete($personDocument);

        return ApiResource::make(['message' => 'deleted']);
    }
}
