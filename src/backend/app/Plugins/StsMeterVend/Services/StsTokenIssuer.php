<?php

namespace App\Plugins\StsMeterVend\Services;

use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Plugins\StsMeterVend\Exceptions\StsVendRefusedException;
use App\Plugins\StsMeterVend\Http\Clients\StsApiClient;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A meter accepts each token identifier (TID) once, and the TID is the issue
 * minute, so every token for one meter has to carry a later minute than the one
 * before. The ledger row is reserved before the engine is called so that a
 * payment vended again gets its token back instead of a second redeemable one.
 */
class StsTokenIssuer {
    private const int MAX_ISSUE_DRIFT_MINUTES = 60;
    private const string MANUFACTURER_TRANSACTION_TYPE = 'sts_transaction';

    public function __construct(
        private StsApiClient $client,
        private StsMeter $stsMeter,
        private StsTransaction $stsTransaction,
    ) {}

    public function issue(Meter $meter, string $tokenType, ?float $kwh, ?Transaction $transaction): StsTransaction {
        [$stsMeter, $issue] = DB::connection('tenant')->transaction(
            fn (): array => $this->reserve($meter, $tokenType, $kwh, $transaction),
        );

        if ($issue->token !== null) {
            return $issue;
        }

        $issue->token = $this->client->generateToken($stsMeter, $issue);
        $issue->save();

        return $issue;
    }

    public function hasKey(Meter $meter): bool {
        return $this->stsMeter->newQuery()->where('mpm_meter_id', $meter->id)->exists();
    }

    public function previousIssue(Transaction $transaction): ?StsTransaction {
        return $this->stsTransaction->newQuery()->where('transaction_id', $transaction->id)->first();
    }

    /**
     * @return array{StsMeter, StsTransaction}
     */
    private function reserve(Meter $meter, string $tokenType, ?float $kwh, ?Transaction $transaction): array {
        $stsMeter = $this->stsMeter->newQuery()
            ->where('mpm_meter_id', $meter->id)
            ->lockForUpdate()
            ->first();

        if (!$stsMeter instanceof StsMeter) {
            throw new StsVendRefusedException("No decoder key on file for meter {$meter->serial_number}.");
        }

        $previousIssue = $transaction instanceof Transaction ? $this->previousIssue($transaction) : null;
        if ($previousIssue instanceof StsTransaction) {
            return [$stsMeter, $previousIssue];
        }

        $issuedAt = $this->nextIssueMinute($stsMeter, $meter);
        $stsMeter->update(['last_issued_at' => $issuedAt]);

        $issue = $this->stsTransaction->newQuery()->create([
            'sts_meter_id' => $stsMeter->id,
            'transaction_id' => $transaction?->id,
            'token_type' => $tokenType,
            'issued_at' => $issuedAt,
            'random_number' => random_int(0, 15),
            'kwh' => $kwh,
        ]);

        $transaction?->originalTransaction()->first()?->update([
            'manufacturer_transaction_id' => $issue->id,
            'manufacturer_transaction_type' => self::MANUFACTURER_TRANSACTION_TYPE,
        ]);

        return [$stsMeter, $issue];
    }

    private function nextIssueMinute(StsMeter $stsMeter, Meter $meter): Carbon {
        $now = Carbon::now()->startOfMinute();
        $issuedAt = $stsMeter->last_issued_at instanceof Carbon
            ? $now->copy()->max($stsMeter->last_issued_at->copy()->addMinute())
            : $now;

        if ($now->diffInMinutes($issuedAt) > self::MAX_ISSUE_DRIFT_MINUTES) {
            throw new StsVendRefusedException("Too many tokens were issued for meter {$meter->serial_number} in a short time. Try again later.");
        }

        return $issuedAt;
    }
}
