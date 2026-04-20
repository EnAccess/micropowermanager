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
                'provider_name' => 'M-Pesa (Vodacom MZ)',
                'template' => 'Confirmado [transaction_ref]. Registamos uma operacao de compra no valor de [amount] e a taxa foi de [*] na entidade [*] com referencia [device_serial] aos [*] as [*]. O teu novo saldo M-Pesa e de [*]. Em caso de dúvida, liga 100. M-Pesa e facil!',
                'sender_pattern' => '/M-?Pesa/i',
                'enabled' => true,
            ],
            [
                'provider_name' => 'e-Mola (Movitel MZ)',
                'template' => 'ID da transacao [transaction_ref]. Transferiste [amount] para conta [sender_phone], nome: [*] as [*] de [*]. Taxa: [*]. O saldo da tua conta e [*]. Conteudo: [device_serial]. Em caso de duvida, liga 100. Obrigado!',
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
