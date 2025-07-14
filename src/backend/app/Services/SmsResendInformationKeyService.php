<?php

namespace App\Services;

use App\Models\SmsResendInformationKey;
use Illuminate\Database\Eloquent\Collection;

class SmsResendInformationKeyService {
    private SmsResendInformationKey $smsResendInformationKey;

    public function __construct(SmsResendInformationKey $smsResendInformationKey) {
        $this->smsResendInformationKey = $smsResendInformationKey;
    }

    /**
     * @return Collection<int, SmsResendInformationKey>
     */
    public function getResendInformationKeys(): Collection {
        return $this->smsResendInformationKey->newQuery()->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateResendInformationKey(SmsResendInformationKey $smsResendInformationKey, array $data): SmsResendInformationKey {
        $smsResendInformationKey->update([
            'key' => $data['key'],
        ]);

        return $smsResendInformationKey->fresh();
    }
}
