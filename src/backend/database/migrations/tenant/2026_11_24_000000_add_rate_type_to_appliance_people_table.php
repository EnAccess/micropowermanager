<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('appliance_people', function (Blueprint $table) {
            $table->string('rate_type', 10)->nullable()->after('rate_count');
        });

        $this->backfill();
    }

    /**
     * Plans sold before the rate type was stored only carry it in the gap between their
     * rates. The last two rates are read because a reschedule rewrites the tail on the new
     * cadence and a down payment only ever sits at the head. The rule is fixed here so the
     * backfill stays the same however the application code changes later.
     */
    public function backfill(): void {
        $tenant = DB::connection('tenant');

        $tenant->table('appliance_people')
            ->where('payment_type', 'installment')
            ->whereNull('rate_type')
            ->orderBy('id')
            ->chunkById(500, function ($appliancePeople) use ($tenant): void {
                foreach ($appliancePeople as $appliancePerson) {
                    $lastTwoDueDates = $tenant->table('appliance_rates')
                        ->where('appliance_person_id', $appliancePerson->id)
                        ->orderByDesc('due_date')
                        ->limit(2)
                        ->pluck('due_date');

                    $isWeekly = $lastTwoDueDates->count() === 2
                        && Carbon::parse($lastTwoDueDates[1])->diffInDays(Carbon::parse($lastTwoDueDates[0])) <= 10;

                    $tenant->table('appliance_people')
                        ->where('id', $appliancePerson->id)
                        ->update(['rate_type' => $isWeekly ? 'weekly' : 'monthly']);
                }
            });
    }

    public function down(): void {
        Schema::connection('tenant')->table('appliance_people', function (Blueprint $table) {
            $table->dropColumn('rate_type');
        });
    }
};
