<?php

namespace App\Services;

use App\Models\Person\Person;
use App\Models\PersonDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles persistence of customer-uploaded documents.
 *
 * Files are written through the default Storage disk so the same code works
 * on the local dev disk and on cloud disks (S3/GCS) in production. Paths are
 * namespaced by company id to keep tenants isolated on a shared bucket — same
 * convention used by MicroStarCertificateService and the Prospect exporters.
 */
class PersonDocumentUploadService {
    public const MAX_DOCUMENTS_PER_PERSON = 3;

    public function __construct(
        private UserService $userService,
    ) {}

    /**
     * @param array<string, mixed>|null $additional
     */
    public function upload(Person $person, UploadedFile $file, string $type, ?array $additional = null): PersonDocument {
        $companyId = $this->userService->getCompanyId();
        $directory = "documents/companies/{$companyId}/persons/{$person->id}";
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::putFileAs($directory, $file, $storedName);

        return PersonDocument::query()->create([
            'person_id' => $person->id,
            'category' => PersonDocument::CATEGORY_CUSTOMER_UPLOAD,
            'type' => $type,
            'name' => $storedName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'location' => $directory,
            'additional_json' => $additional,
        ]);
    }

    /**
     * @param array<string, mixed>|null $additional
     */
    public function updateAdditional(PersonDocument $document, ?array $additional): PersonDocument {
        $document->additional_json = $additional;
        $document->save();

        return $document;
    }

    public function download(PersonDocument $document): StreamedResponse {
        $path = $this->fullPath($document);

        return Storage::download($path, $document->original_name ?? $document->name);
    }

    public function delete(PersonDocument $document): void {
        $path = $this->fullPath($document);

        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        $document->delete();
    }

    private function fullPath(PersonDocument $document): string {
        return trim((string) $document->location, '/').'/'.$document->name;
    }
}
