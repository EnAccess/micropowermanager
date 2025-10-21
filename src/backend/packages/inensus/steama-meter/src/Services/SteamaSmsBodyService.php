<?php

namespace Inensus\SteamaMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\SteamaMeter\Models\SteamaSmsBody;

class SteamaSmsBodyService {
    public function __construct(
        private SteamaSmsBody $smsBody,
    ) {}

    public function getSmsBodyByReference(string $reference): SteamaSmsBody {
        return $this->smsBody->newQuery()->where('reference', $reference)->firstOrFail();
    }

    /**
     * @return Collection<int, SteamaSmsBody>
     */
    public function getSmsBodies(): Collection {
        return $this->smsBody->newQuery()->get();
    }

    /**
     * @param array<string, mixed> $smsBodiesData
     *
     * @return Collection<int, SteamaSmsBody>
     */
    public function updateSmsBodies(array $smsBodiesData): Collection {
        $smsBodies = $this->smsBody->newQuery()->get();
        collect($smsBodiesData)->each(function (array $smsBody) use ($smsBodies) {
            $smsBodies->filter(fn (SteamaSmsBody $body): bool => $body['id'] === $smsBody['id'])->first()->update([
                'body' => $smsBody['body'],
            ]);
        });

        return $smsBodies;
    }

    /**
     * @return Collection<int, SteamaSmsBody>
     */
    public function getNullBodies(): Collection {
        return $this->smsBody->newQuery()->whereNull('body')->get();
    }

    public function createSmsBodies(): void {
        $smsBodies = [
            [
                'reference' => 'SteamaSmsLowBalanceHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SteamaSmsLowBalanceBody',
                'place_holder' => 'your credit balance has reduced under [low_balance_warning],'
                    .'your currently balance is [account_balance]',
                'variables' => 'low_balance_warning,account_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackBody',
                'place_holder' => 'your currently balance is [account_balance]',
                'variables' => 'account_balance',
                'title' => 'Balance Feedback',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SteamaSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        collect($smsBodies)->each(function (array $smsBody) {
            $this->smsBody->newQuery()->firstOrCreate(['reference' => $smsBody['reference']], $smsBody);
        });
    }
}
