<?php

namespace App\Services;

use App\Models\SmsBody;
use Illuminate\Database\Eloquent\Collection;

class SmsBodyService {
    private SmsBody $smsBody;

    public function __construct(SmsBody $smsBody) {
        $this->smsBody = $smsBody;
    }

    public function getSmsBodyByReference(string $reference): SmsBody {
        return $this->smsBody->newQuery()->where('reference', $reference)->firstOrFail();
    }

    public function getSmsBodies(): Collection {
        return $this->smsBody->newQuery()->get();
    }

    /**
     * @param array<int, array<string, mixed>> $smsBodiesData
     */
    public function updateSmsBodies(array $smsBodiesData): Collection {
        $smsBodies = $this->smsBody->newQuery()->get();
        collect($smsBodiesData[0])->each(function ($smsBody) use ($smsBodies) {
            $smsBodies->filter(function ($body) use ($smsBody) {
                return $body['id'] === $smsBody['id'];
            })->first()->update([
                'body' => $smsBody['body'],
            ]);
        });

        return $smsBodies;
    }

    public function getNullBodies(): Collection {
        return $this->smsBody->newQuery()->whereNull('body')->get();
    }
}
