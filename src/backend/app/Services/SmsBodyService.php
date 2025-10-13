<?php

namespace App\Services;

use App\Models\SmsBody;
use Illuminate\Database\Eloquent\Collection;

class SmsBodyService {
    public function __construct(private SmsBody $smsBody) {}

    public function getSmsBodyByReference(string $reference): SmsBody {
        return $this->smsBody->newQuery()->where('reference', $reference)->firstOrFail();
    }

    /**
     * @return Collection<int, SmsBody>
     */
    public function getSmsBodies(): Collection {
        return $this->smsBody->newQuery()->get();
    }

    /**
     * @param array<int, array<string, mixed>> $smsBodiesData
     *
     * @return Collection<int, SmsBody>
     */
    public function updateSmsBodies(array $smsBodiesData): Collection {
        $smsBodies = $this->smsBody->newQuery()->get();
        collect($smsBodiesData[0])->each(function (array $smsBody) use ($smsBodies) {
            $smsBodies->filter(fn (SmsBody $body): bool => $body['id'] === $smsBody['id'])->first()->update([
                'body' => $smsBody['body'],
            ]);
        });

        return $smsBodies;
    }

    /**
     * @return Collection<int, SmsBody>
     */
    public function getNullBodies(): Collection {
        return $this->smsBody->newQuery()->whereNull('body')->get();
    }
}
