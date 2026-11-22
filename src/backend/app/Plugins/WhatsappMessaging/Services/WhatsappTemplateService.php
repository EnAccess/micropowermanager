<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MainSettings;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Sms\Senders\ApplianceRateNotification;
use App\Sms\Senders\OverDueApplianceRateNotification;
use App\Sms\Senders\SmsConfigs;
use App\Sms\Senders\TransactionConfirmation;
use Illuminate\Database\Eloquent\Collection;

class WhatsappTemplateService {
    public const array NOTIFICATIONS = [
        'TokenConfirmationMeter' => ['template' => 'token_confirmation_meter', 'sender' => TransactionConfirmation::class],
        'TokenConfirmationSHS' => ['template' => 'token_confirmation_shs', 'sender' => TransactionConfirmation::class],
        'TransactionConfirmationNoToken' => ['template' => 'transaction_confirmation_no_token', 'sender' => TransactionConfirmation::class],
        'ApplianceRateReminder' => ['template' => 'appliance_rate_reminder', 'sender' => ApplianceRateNotification::class],
        'OverDueApplianceRateReminder' => ['template' => 'over_due_appliance_rate_reminder', 'sender' => OverDueApplianceRateNotification::class],
    ];

    private const string TEMPLATE_FIELDS = 'id,name,language,category,status,parameter_format,components,rejected_reason';
    private const array DELETION_EVENTS = ['DELETED', 'PENDING_DELETION'];

    public const array META_SAMPLE_TEMPLATES = ['hello_world'];

    public function __construct(
        private WhatsappTemplate $template,
        private MainSettings $mainSettings,
        private SmsConfigs $smsConfigs,
        private WhatsappApiClient $apiClient,
    ) {}

    /**
     * @param array<string, string> $values the sender's parser values, keyed by variable name
     *
     * @return array<string, mixed>
     */
    public function notificationMessage(?string $reference, array $values): array {
        return $this->notificationTemplate((string) $reference)->message($values);
    }

    /**
     * Sending and the Templates page both ask this, so a notification the page shows as ready is one that sends.
     *
     * @throws WhatsappTemplateNotConfiguredException with the reason, which the operator sees on the SMS that went instead
     */
    public function notificationTemplate(string $reference): WhatsappTemplate {
        $name = self::NOTIFICATIONS[$reference]['template']
            ?? throw new WhatsappTemplateNotConfiguredException('This message has no WhatsApp template, so it goes by SMS once 24 hours have passed since the customer last wrote.');

        $templates = $this->template->newQuery()->where('name', $name)->get();
        if ($templates->isEmpty()) {
            throw new WhatsappTemplateNotConfiguredException(sprintf('There is no WhatsApp template named "%s". Create it in WhatsApp Manager.', $name));
        }

        $template = $this->inCompanyLanguage($templates->where('status', WhatsappTemplate::STATUS_APPROVED))
            ?? throw new WhatsappTemplateNotConfiguredException(sprintf('The WhatsApp template "%s" is not approved (status: %s).', $name, $templates->first()->status));

        if ($template->parameter_format !== WhatsappTemplate::PARAMETER_FORMAT_NAMED) {
            throw new WhatsappTemplateNotConfiguredException(sprintf('The WhatsApp template "%s" must use named parameters such as {{name}}, not {{1}}.', $name));
        }

        if (!$template->canBeFilledWithText()) {
            throw new WhatsappTemplateNotConfiguredException(sprintf('The WhatsApp template "%s" has a media header or a button with a variable, which MPM cannot fill.', $name));
        }

        $unknown = array_diff($template->parameterNames(), $this->notificationParameters($reference));
        if ($unknown !== []) {
            throw new WhatsappTemplateNotConfiguredException(sprintf('The WhatsApp template "%s" uses parameters MPM does not have: %s.', $name, implode(', ', $unknown)));
        }

        return $template;
    }

    /** @return list<array{reference: string, template_name: string, parameters: list<string>, problem: string|null}> */
    public function notificationStatuses(): array {
        return array_map(function (string $reference): array {
            try {
                $this->notificationTemplate($reference);
                $problem = null;
            } catch (WhatsappTemplateNotConfiguredException $exception) {
                $problem = $exception->getMessage();
            }

            return [
                'reference' => $reference,
                'template_name' => self::NOTIFICATIONS[$reference]['template'],
                'parameters' => $this->notificationParameters($reference),
                'problem' => $problem,
            ];
        }, array_keys(self::NOTIFICATIONS));
    }

    /** The notification a template is reserved for by its name, or null when operators may pick it for manual messages. */
    public function notificationFor(WhatsappTemplate $template): ?string {
        foreach (self::NOTIFICATIONS as $reference => $notification) {
            if ($notification['template'] === $template->name) {
                return $reference;
            }
        }

        return null;
    }

    /** @return Collection<int, WhatsappTemplate> the templates the send screens offer operators */
    public function manualTemplates(): Collection {
        return $this->template->newQuery()->where('status', WhatsappTemplate::STATUS_APPROVED)->orderBy('name')->get()
            ->filter(fn (WhatsappTemplate $template): bool => $this->isForManualMessages($template))
            ->values();
    }

    /**
     * The template an operator picked for a manual message; the picker only offers these, but the request may be stale or hand-made.
     *
     * @throws WhatsappTemplateNotConfiguredException
     */
    public function manualTemplate(string $metaTemplateId): WhatsappTemplate {
        $template = $this->template->newQuery()->where('meta_template_id', $metaTemplateId)->first()
            ?? throw new WhatsappTemplateNotConfiguredException('The chosen WhatsApp template no longer exists in WhatsApp Manager.');

        if ($template->status !== WhatsappTemplate::STATUS_APPROVED) {
            throw new WhatsappTemplateNotConfiguredException(sprintf('The WhatsApp template "%s" is not approved (status: %s).', $template->name, $template->status));
        }

        if (!$this->isForManualMessages($template)) {
            throw new WhatsappTemplateNotConfiguredException(sprintf('The WhatsApp template "%s" cannot be used for manual messages.', $template->name));
        }

        return $template;
    }
    private function isForManualMessages(WhatsappTemplate $template): bool {
        return $this->notificationFor($template) === null
            && !in_array($template->name, self::META_SAMPLE_TEMPLATES, true)
            && $template->canBeFilledWithText();
    }

    /** @return list<string> the variables of the notification's body parser and its sender's header parser */
    public function notificationParameters(string $reference): array {
        $header = new \ReflectionClass(self::NOTIFICATIONS[$reference]['sender'])->getDefaultProperties()['references']['header'] ?? null;

        return array_values(array_merge(...array_map(
            fn (string $parser): array => new \ReflectionClass($this->smsConfigs->bodyParsersPath.$parser)->getDefaultProperties()['variables'] ?? [],
            array_filter([$header, $reference]),
        )));
    }
    public function refresh(WhatsappCredential $credential): void {
        $query = ['fields' => self::TEMPLATE_FIELDS, 'limit' => 100];
        $storedIds = [];

        do {
            $response = $this->apiClient->get($credential, $credential->business_account_id.'/message_templates', $query);
            foreach ($response->json('data', []) as $metaTemplate) {
                if (!in_array($metaTemplate['name'] ?? null, self::META_SAMPLE_TEMPLATES, true)) {
                    $storedIds[] = $this->store($metaTemplate)->id;
                }
            }
            $query['after'] = $response->json('paging.cursors.after');
        } while ($response->json('paging.next') !== null);

        $this->template->newQuery()->whereKeyNot($storedIds)->delete();
    }

    /**
     * @param array<string, mixed> $value
     */
    public function applyWebhook(WhatsappCredential $credential, array $value): void {
        $metaTemplateId = (string) ($value['message_template_id'] ?? '');

        if (in_array($value['event'] ?? null, self::DELETION_EVENTS, true)) {
            $this->template->newQuery()->where('meta_template_id', $metaTemplateId)->delete();

            return;
        }

        $metaTemplate = $this->apiClient->get($credential, $metaTemplateId, ['fields' => self::TEMPLATE_FIELDS])->json();
        if (in_array($metaTemplate['name'] ?? null, self::META_SAMPLE_TEMPLATES, true)) {
            return;
        }

        $template = $this->store($metaTemplate);
        $template->update(array_filter([
            'rejection_detail' => $value['rejection_info']['reason'] ?? null,
            'rejection_recommendation' => $value['rejection_info']['recommendation'] ?? null,
            'status_detail' => $value['other_info']['description'] ?? null,
        ]));
    }

    /** @param array<string, mixed> $metaTemplate */
    private function store(array $metaTemplate): WhatsappTemplate {
        $template = $this->template->newQuery()->firstOrNew(['meta_template_id' => (string) $metaTemplate['id']]);
        if ($template->exists && $template->status !== $metaTemplate['status']) {
            $template->fill(['rejection_detail' => null, 'rejection_recommendation' => null, 'status_detail' => null]);
        }

        $rejectedReason = $metaTemplate['rejected_reason'] ?? 'NONE';
        $template->fill([
            'name' => $metaTemplate['name'],
            'language' => $metaTemplate['language'],
            'category' => $metaTemplate['category'],
            'status' => $metaTemplate['status'],
            'parameter_format' => $metaTemplate['parameter_format'] ?? WhatsappTemplate::PARAMETER_FORMAT_POSITIONAL,
            'components' => $metaTemplate['components'] ?? [],
            'rejected_reason' => $rejectedReason === 'NONE' ? null : $rejectedReason,
        ])->save();

        return $template;
    }

    /**
     * @param Collection<int, WhatsappTemplate> $templates
     */
    private function inCompanyLanguage(Collection $templates): ?WhatsappTemplate {
        $language = (string) $this->mainSettings->newQuery()->value('language');

        return $templates->first(fn (WhatsappTemplate $template): bool => strtok($template->language, '_') === $language) ?? $templates->first();
    }
}
