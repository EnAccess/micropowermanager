<?php

namespace App\Services;

use App\Models\SmsBody;

class SmsBodyService
{
    private $smsBody;

    public function __construct(SmsBody $smsBody)
    {
        $this->smsBody = $smsBody;
    }

    public function getSmsBodyByReference($reference)
    {
        return  $this->smsBody->newQuery()->where('reference', $reference)->firstOrFail();
    }

    public function getSmsBodies()
    {
        return $this->smsBody->newQuery()->get();
    }

    public function updateSmsBodies($smsBodiesData)
    {

        $smsBodies = $this->smsBody->newQuery()->get();
        collect($smsBodiesData)->each(function ($smsBody) use ($smsBodies) {
            $smsBodies->filter(function ($body) use ($smsBody) {
                return $body['id'] === $smsBody['id'];
            })->first()->update([
                'body' => $smsBody['body']
            ]);
        });
        return $smsBodies;
    }

    public function getNullBodies()
    {
        return $this->smsBody->newQuery()->whereNull('body')->get();
    }
}
