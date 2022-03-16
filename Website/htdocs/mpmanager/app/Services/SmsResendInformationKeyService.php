<?php

namespace App\Services;

use App\Models\SmsResendInformationKey;

class SmsResendInformationKeyService
{
    private $smsResendInformationKey;
    public function __construct(SmsResendInformationKey $smsResendInformationKey)
    {
        $this->smsResendInformationKey = $smsResendInformationKey;
    }

    public function getResendInformationKeys()
    {
        return $this->smsResendInformationKey->newQuery()->get();
    }

    public function updateResendInformationKey(SmsResendInformationKey $smsResendInformationKey, $data)
    {
        $smsResendInformationKey->update([
            'key' => $data['key']
        ]);
        return $smsResendInformationKey->fresh();
    }
}
