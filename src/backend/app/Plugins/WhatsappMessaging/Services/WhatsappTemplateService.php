<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Models\SmsBody;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappTemplateFormat;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Sms\Senders\SmsConfigs;
use App\Sms\Senders\SmsSender;

class WhatsappTemplateService {
    public function __construct(private SmsBody $smsBody, private MainSettings $mainSettings, private SmsConfigs $smsConfigs, private WhatsappApiClient $apiClient) {}

    /**
     * @param array<string, string> $values the body parser's values, keyed by variable name
     *
     * @return array<string, mixed>
     */
    public function message(?string $reference, array $values): array {
        $smsText = $this->smsTexts()[(string) $reference] ?? throw new WhatsappTemplateNotConfiguredException('This message is not an MPM notification text, so it has no WhatsApp template.');

        return WhatsappTemplateFormat::message((string) $reference, $smsText, $this->companyName(), $values);
    }

    /** @return list<string> one message per template Meta refused; one refusal must not stop the others */
    public function sync(WhatsappCredential $credential): array {
        $path = $credential->business_account_id.'/message_templates';
        $errors = [];

        foreach ($this->smsTexts() as $reference => $smsText) {
            $definition = WhatsappTemplateFormat::definition($reference, $smsText, $this->companyName());
            try {
                // Meta's name filter also matches partially, so only an exact name counts as existing.
                if (!in_array($definition['name'], array_column($this->apiClient->get($credential, $path, ['name' => $definition['name']])->json('data', []), 'name'), true)) {
                    $this->apiClient->post($credential, $path, $definition);
                }
            } catch (WhatsappApiException $exception) {
                $errors[] = $definition['name'].': '.$exception->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Keyed by the senders' spelling, not the rows': they differ in case (OverDue/Overdue), and sync and send must name a template alike.
     *
     * @return array<string, string> each notification text that is set, keyed by its sender reference
     */
    private function smsTexts(): array {
        $references = array_merge(...array_map(fn (string $sender): array => is_subclass_of($sender, SmsSender::class) ? $sender::bodyReferences() : [], array_values($this->smsConfigs->smsTypes)));
        $bodies = array_change_key_case($this->smsBody->newQuery()->whereIn('reference', $references)->pluck('body', 'reference')->all());

        return array_filter(array_combine($references, array_map(fn (string $reference): string => (string) ($bodies[strtolower($reference)] ?? ''), $references)));
    }

    // The company name opens every template, giving Meta the sender context it asks for.
    private function companyName(): string {
        return (string) $this->mainSettings->newQuery()->value('company_name') ?: 'Account update';
    }
}
