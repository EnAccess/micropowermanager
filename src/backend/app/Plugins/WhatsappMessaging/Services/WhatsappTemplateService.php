<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\SmsBody;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappTemplateFormat;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Sms\Senders\SmsConfigs;
use App\Sms\Senders\SmsSender;

class WhatsappTemplateService {
    public function __construct(private SmsBody $smsBody, private SmsConfigs $smsConfigs, private WhatsappApiClient $apiClient) {}

    /**
     * @param array<string, string> $values the body parser's values, keyed by variable name
     *
     * @return array<string, mixed>
     */
    public function message(?string $reference, array $values): array {
        $smsText = in_array($reference, $this->references(), true) ? $this->smsBody->newQuery()->where('reference', $reference)->value('body') : null;

        if (!is_string($smsText) || $smsText === '') {
            throw new WhatsappTemplateNotConfiguredException('This message is not an MPM notification text, so it has no WhatsApp template.');
        }

        return WhatsappTemplateFormat::message((string) $reference, $smsText, $values);
    }

    /**
     * Submits every MPM notification text that has no template yet; one refusal must not stop the others.
     *
     * @return list<string> one message per template Meta refused
     */
    public function sync(WhatsappCredential $credential): array {
        $path = $credential->business_account_id.'/message_templates';
        $errors = [];

        foreach ($this->smsBody->newQuery()->whereIn('reference', $this->references())->whereNotNull('body')->get() as $smsBody) {
            $name = WhatsappTemplateFormat::name($smsBody->reference);
            try {
                // Meta's name filter also matches partially, so only an exact name counts as existing.
                if (!in_array($name, array_column($this->apiClient->get($credential, $path, ['name' => $name])->json('data', []), 'name'), true)) {
                    $this->apiClient->post($credential, $path, WhatsappTemplateFormat::definition($smsBody->reference, (string) $smsBody->body));
                }
            } catch (WhatsappApiException $exception) {
                $errors[] = $name.': '.$exception->getMessage();
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function references(): array {
        return array_merge(...array_map(fn (string $sender): array => is_subclass_of($sender, SmsSender::class) ? $sender::bodyReferences() : [], array_values($this->smsConfigs->smsTypes)));
    }
}
