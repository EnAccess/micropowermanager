<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Services;

use App\Plugins\SmsTransactionParser\Models\SmsParsingRule;
use App\Plugins\SmsTransactionParser\SmsParsing\TemplateToRegexConverter;
use Illuminate\Database\Eloquent\Collection;

class SmsParsingRuleService {
    public function __construct(
        private SmsParsingRule $smsParsingRule,
    ) {}

    /**
     * @return Collection<int, SmsParsingRule>
     */
    public function getAll(): Collection {
        return $this->smsParsingRule->newQuery()->get();
    }

    public function getById(int $id): SmsParsingRule {
        return $this->smsParsingRule->newQuery()->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): SmsParsingRule {
        return $this->smsParsingRule->newQuery()->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(SmsParsingRule $rule, array $data): SmsParsingRule {
        $rule->update($data);

        return $rule->fresh();
    }

    public function delete(SmsParsingRule $rule): void {
        $rule->delete();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDefaultRule(array $data): SmsParsingRule {
        return $this->smsParsingRule->newQuery()->firstOrCreate(
            ['provider_name' => $data['provider_name']],
            $data,
        );
    }

    /**
     * @return Collection<int, SmsParsingRule>
     */
    public function installDefaults(): Collection {
        $converter = app(TemplateToRegexConverter::class);

        $defaults = [
            [
                'provider_name' => 'vodacom_en',
                'template' => 'Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]',
                'sender_pattern' => '/M-?Pesa/i',
                'enabled' => true,
            ],
            [
                'provider_name' => 'vodacom_pt',
                'template' => 'Confirmado [transaction_ref].[*]valor de [amount]MT[*]referencia [device_serial][*]',
                'sender_pattern' => '/M-?Pesa/i',
                'enabled' => true,
            ],
            [
                'provider_name' => 'movitel_pt',
                'template' => 'ID da transacao[*][transaction_ref].[*][amount]MT[*]Conteudo:[*][device_serial].[*]',
                'sender_pattern' => '/e-?Mola/i',
                'enabled' => true,
            ],
            [
                'provider_name' => 'movitel_en',
                'template' => 'Transaction ID [transaction_ref].[*][amount] MT[*]Content:[*][device_serial].[*]',
                'sender_pattern' => '/e-?Mola/i',
                'enabled' => true,
            ],
        ];

        foreach ($defaults as $default) {
            $default['pattern'] = $converter->convert($default['template']);
            $this->createDefaultRule($default);
        }

        return $this->getAll();
    }
}
