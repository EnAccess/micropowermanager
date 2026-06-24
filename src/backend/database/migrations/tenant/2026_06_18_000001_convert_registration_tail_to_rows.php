<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $connection = DB::connection('tenant');
        $schema = Schema::connection('tenant');

        $steps = [];
        if ($schema->hasColumn('registration_tail', 'tail')) {
            foreach ($connection->table('registration_tail')->get() as $row) {
                $entries = empty($row->tail) ? [] : json_decode($row->tail, true);
                if (!is_array($entries)) {
                    continue;
                }
                foreach ($entries as $entry) {
                    $steps[] = [
                        'component' => $entry['component'] ?? $entry['tag'] ?? null,
                        'adjusted' => !empty($entry['adjusted']),
                        'updated_by' => $row->updated_by ?? null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }
            }
        }

        $schema->table('registration_tail', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('registration_tail', 'component')) {
                $table->string('component')->nullable()->after('id');
            }
            if (!$schema->hasColumn('registration_tail', 'adjusted')) {
                $table->boolean('adjusted')->default(false)->after('component');
            }
        });

        // Drop the json column before inserting rows: it is NOT NULL with no
        // default, so leaving it in place would reject the new step rows.
        if ($schema->hasColumn('registration_tail', 'tail')) {
            $schema->table('registration_tail', function (Blueprint $table) {
                $table->dropColumn('tail');
            });
        }

        if ($steps !== []) {
            $connection->table('registration_tail')->delete();
            $connection->table('registration_tail')->insert($steps);
        }
    }

    public function down(): void {
        $connection = DB::connection('tenant');
        $schema = Schema::connection('tenant');

        $tail = $connection->table('registration_tail')->get()->map(fn ($row) => [
            'tag' => $row->component,
            'component' => $row->component,
            'adjusted' => (bool) $row->adjusted,
        ])->values()->all();
        $updatedBy = optional($connection->table('registration_tail')->first())->updated_by;

        if (!$schema->hasColumn('registration_tail', 'tail')) {
            $schema->table('registration_tail', function (Blueprint $table) {
                $table->json('tail')->nullable();
            });
        }

        $connection->table('registration_tail')->delete();
        $connection->table('registration_tail')->insert([
            'tail' => json_encode($tail),
            'updated_by' => $updatedBy,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $schema->table('registration_tail', function (Blueprint $table) {
            $table->dropColumn(['component', 'adjusted']);
        });
    }
};
