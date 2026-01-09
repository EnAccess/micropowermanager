<?php

namespace Inensus\SparkMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\SparkMeter\Models\SmSmsBody;

class SmSmsBodyService {
    public function __construct(
        private SmSmsBody $smsBody,
    ) {}

    public function getSmsBodyByReference(string $reference): SmSmsBody {
        return $this->smsBody->newQuery()->where('reference', $reference)->firstOrFail();
    }

    /**
     * @return Collection<int, SmSmsBody>
     */
    public function getSmsBodies(): Collection {
        return $this->smsBody->newQuery()->get();
    }

    /**
     * @param array<string, mixed> $smsBodiesData
     *
     * @return Collection<int, SmSmsBody>
     */
    public function updateSmsBodies(array $smsBodiesData): Collection {
        $smsBodies = $this->smsBody->newQuery()->get();
        collect($smsBodiesData)->each(function (array $smsBody) use ($smsBodies) {
            $smsBodies->filter(fn (SmSmsBody $body): bool => $body['id'] === $smsBody['id'])->first()->update([
                'body' => $smsBody['body'],
            ]);
        });

        return $smsBodies;
    }

    /**
     * @return Collection<int, SmSmsBody>
     */
    public function getNullBodies(): Collection {
        return $this->smsBody->newQuery()->whereNull('body')->get();
    }

    public function createSmsBodies(): void {
        $smsBodies = [
            [
                'reference' => 'SparkSmsLowBalanceHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsLowBalanceBody',
                'place_holder' => 'your credit balance has reduced under [low_balance_limit],'
                    .'your currently balance is [credit_balance]',
                'variables' => 'low_balance_limit,credit_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackBody',
                'place_holder' => 'your currently balance is [credit_balance]',
                'variables' => 'credit_balance',
                'title' => 'Balance Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackBody',
                'place_holder' => 'your meter, [meter_serial] has reset successfully.',
                'variables' => 'meter_serial',
                'title' => 'Meter Reset Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFailedFeedbackBody',
                'place_holder' => 'meter reset failed with [meter_serial].',
                'variables' => 'meter_serial',
                'title' => 'Meter Reset Failed Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SparkSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        collect($smsBodies)->each(function (array $smsBody) {
            $this->smsBody->newQuery()->firstOrCreate(
                ['reference' => $smsBody['reference']],
                $smsBody
            );
        });
    }
}
