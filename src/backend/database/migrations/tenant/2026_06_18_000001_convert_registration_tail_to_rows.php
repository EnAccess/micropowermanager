<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $steps = [];
        foreach (DB::connection('tenant')->table('registration_tail')->get() as $row) {
            $entries = empty($row->tail) ? [] : json_decode($row->tail, true);
            if (!is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                $steps[] = [
                    'tag' => $entry['tag'] ?? null,
                    'component' => $entry['component'] ?? null,
                    'adjusted' => !empty($entry['adjusted']),
                    'updated_by' => $row->updated_by ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        Schema::connection('tenant')->table('registration_tail', function (Blueprint $table) {
            $table->string('tag')->nullable()->after('id');
            $table->string('component')->nullable()->after('tag');
            $table->boolean('adjusted')->default(false)->after('component');
        });

        DB::connection('tenant')->table('registration_tail')->delete();

        if ($steps !== []) {
            DB::connection('tenant')->table('registration_tail')->insert($steps);
        }

        Schema::connection('tenant')->table('registration_tail', function (Blueprint $table) {
            $table->dropColumn('tail');
        });
    }

    public function down(): void {
        $rows = DB::connection('tenant')->table('registration_tail')->get();

        $tail = $rows->map(fn ($row) => [
            'tag' => $row->tag,
            'component' => $row->component,
            'adjusted' => (bool) $row->adjusted,
        ])->values()->all();

        Schema::connection('tenant')->table('registration_tail', function (Blueprint $table) {
            $table->json('tail')->nullable();
        });

        DB::connection('tenant')->table('registration_tail')->delete();
        DB::connection('tenant')->table('registration_tail')->insert([
            'tail' => json_encode($tail),
            'updated_by' => optional($rows->first())->updated_by,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Schema::connection('tenant')->table('registration_tail', function (Blueprint $table) {
            $table->dropColumn(['tag', 'component', 'adjusted']);
        });
    }
};
