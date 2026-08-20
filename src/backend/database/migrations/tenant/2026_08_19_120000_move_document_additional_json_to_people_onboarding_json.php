<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (
            Schema::connection('tenant')->hasColumn('people', 'additional_json')
            && !Schema::connection('tenant')->hasColumn('people', 'onboarding_json')
        ) {
            Schema::connection('tenant')->table('people', function (Blueprint $table) {
                $table->renameColumn('additional_json', 'onboarding_json');
            });
        }

        if (!Schema::connection('tenant')->hasColumn('person_documents', 'additional_json')) {
            return;
        }

        $this->moveAnswersToPeople(DB::connection('tenant'));

        Schema::connection('tenant')->table('person_documents', function (Blueprint $table) {
            $table->dropColumn('additional_json');
        });
    }

    public function down(): void {
        // Which document an answer came from was never recorded, so the answers stay on the
        // person and the restored column comes back empty. The column itself has to come back:
        // 2026_05_20_120000_add_uploaded_file_fields_to_person_documents_table drops it by name
        // in its own down(), which fails on a missing column.
        if (!Schema::connection('tenant')->hasColumn('person_documents', 'additional_json')) {
            Schema::connection('tenant')->table('person_documents', function (Blueprint $table) {
                $table->json('additional_json')->nullable()->after('file_size');
            });
        }

        if (
            Schema::connection('tenant')->hasColumn('people', 'onboarding_json')
            && !Schema::connection('tenant')->hasColumn('people', 'additional_json')
        ) {
            Schema::connection('tenant')->table('people', function (Blueprint $table) {
                $table->renameColumn('onboarding_json', 'additional_json');
            });
        }
    }

    // The query builder is used instead of Eloquent because Person soft-deletes (trashed owners
    // of documents would be skipped), appends is_active (a payment query per model) and its
    // casts() is renamed in the same change set.
    private function moveAnswersToPeople(Connection $connection): void {
        /** @var array<int, array<string, mixed>> $answersByPerson */
        $answersByPerson = [];

        $connection->table('person_documents')
            ->select(['id', 'person_id', 'additional_json'])
            ->whereNotNull('additional_json')
            ->orderBy('person_id')
            ->orderBy('id')
            ->each(function (stdClass $document) use (&$answersByPerson): void {
                $answers = $this->flatAnswers($document->additional_json);

                if ($answers === []) {
                    return;
                }

                $personId = (int) $document->person_id;
                // The lowest document id wins on a repeated question.
                $answersByPerson[$personId] = ($answersByPerson[$personId] ?? []) + $answers;
            });

        foreach (array_chunk($answersByPerson, 500, true) as $chunk) {
            $existingAnswers = $connection->table('people')
                ->select(['id', 'onboarding_json'])
                ->whereIn('id', array_keys($chunk))
                ->pluck('onboarding_json', 'id');

            foreach ($chunk as $personId => $answers) {
                // Anything already on the person outranks anything inherited from a document.
                $merged = $this->flatAnswers($existingAnswers->get($personId)) + $answers;

                $connection->table('people')
                    ->where('id', $personId)
                    // JSON_FORCE_OBJECT keeps a person whose questions are "0"/"1" from being
                    // encoded as a JSON array and dropped on the next read.
                    ->update(['onboarding_json' => json_encode($merged, JSON_FORCE_OBJECT)]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function flatAnswers(mixed $json): array {
        $decoded = json_decode(is_string($json) ? $json : '', true);

        if (!is_array($decoded) || array_is_list($decoded)) {
            return [];
        }

        return array_filter($decoded, fn ($answer): bool => !is_array($answer));
    }
};
