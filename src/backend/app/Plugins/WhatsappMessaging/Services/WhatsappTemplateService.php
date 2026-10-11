<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type MetaTemplate array{name: string, language: string, status: string, category: string|null, placeholder_count: int}
 */
class WhatsappTemplateService {
    public const string META_STATUS_APPROVED = 'APPROVED';

    private const string DEFAULT_LANGUAGE = 'en';

    // Each parameter list matches the {{n}} order of the template body submitted in WhatsApp Manager.
    private const array DEFAULT_MAPPINGS = [
        'TokenConfirmationMeter' => [
            'template_name' => 'mpm_payment_receipt_meter',
            'parameters' => ['name', 'surname', 'meter_serial', 'token', 'energy'],
        ],
        'TokenConfirmationSHS' => [
            'template_name' => 'mpm_payment_receipt_shs',
            'parameters' => ['name', 'surname', 'amount', 'device_serial', 'token', 'duration', 'unit'],
        ],
        'TransactionConfirmationNoToken' => [
            'template_name' => 'mpm_payment_receipt',
            'parameters' => ['name', 'surname', 'amount'],
        ],
        'ApplianceRateReminder' => [
            'template_name' => 'mpm_appliance_payment_reminder',
            'parameters' => ['remaining', 'appliance_type_name', 'due_date'],
        ],
        'OverDueApplianceRateReminder' => [
            'template_name' => 'mpm_appliance_payment_overdue',
            'parameters' => ['remaining', 'appliance_type_name', 'due_date'],
        ],
    ];

    public function __construct(
        private WhatsappTemplate $template,
        private WhatsappCloudApiClient $cloudApiClient,
    ) {}

    public function createDefaultMappings(): void {
        foreach (self::DEFAULT_MAPPINGS as $bodyParser => $mapping) {
            $this->template->newQuery()->firstOrCreate(
                ['body_parser' => $bodyParser, 'language_code' => self::DEFAULT_LANGUAGE],
                $mapping + ['is_active' => true],
            );
        }
    }

    public function findActive(string $bodyParser, string $language): ?WhatsappTemplate {
        return $this->template->newQuery()
            ->where('body_parser', $bodyParser)
            ->where('language_code', $language)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return Collection<int, WhatsappTemplate>
     */
    public function getAll(): Collection {
        return $this->template->newQuery()->orderBy('body_parser')->get();
    }

    /**
     * Templates are reviewed and approved in WhatsApp Manager, so Meta is the only source of their status.
     *
     * @return list<MetaTemplate>
     *
     * @throws WhatsappMessageNotSentException
     */
    public function syncFromMeta(): array {
        return array_map(fn (array $metaTemplate): array => [
            'name' => $metaTemplate['name'],
            'language' => $metaTemplate['language'],
            'status' => $metaTemplate['status'],
            'category' => $metaTemplate['category'] ?? null,
            'placeholder_count' => $this->countBodyPlaceholders($metaTemplate['components'] ?? []),
        ], $this->cloudApiClient->getMessageTemplates());
    }

    /**
     * @param list<MetaTemplate> $metaTemplates the result of syncFromMeta()
     *
     * @return array{template: WhatsappTemplate, meta_template: MetaTemplate|null, problems: list<string>}
     */
    public function getMappingStatus(WhatsappTemplate $template, array $metaTemplates): array {
        return [
            'template' => $template,
            'meta_template' => $this->findMetaTemplate($template, $metaTemplates),
            'problems' => $this->validateMapping($template, $metaTemplates),
        ];
    }

    /**
     * @param list<MetaTemplate> $metaTemplates the result of syncFromMeta()
     *
     * @return list<string> the reasons the mapping cannot be sent; empty when it is valid
     */
    public function validateMapping(WhatsappTemplate $template, array $metaTemplates): array {
        $metaTemplate = $this->findMetaTemplate($template, $metaTemplates);

        if ($metaTemplate === null) {
            return [sprintf('Template "%s" (%s) does not exist in WhatsApp Manager.', $template->template_name, $template->language_code)];
        }

        $problems = [];

        if ($metaTemplate['status'] !== self::META_STATUS_APPROVED) {
            $problems[] = sprintf('Template "%s" is %s in WhatsApp Manager, not %s.', $template->template_name, $metaTemplate['status'], self::META_STATUS_APPROVED);
        }

        if ($metaTemplate['placeholder_count'] !== count($template->parameters)) {
            $problems[] = sprintf(
                'Template "%s" has %d placeholders but %d parameters are mapped.',
                $template->template_name,
                $metaTemplate['placeholder_count'],
                count($template->parameters),
            );
        }

        return $problems;
    }

    /**
     * @param list<MetaTemplate> $metaTemplates
     *
     * @return MetaTemplate|null
     */
    private function findMetaTemplate(WhatsappTemplate $template, array $metaTemplates): ?array {
        foreach ($metaTemplates as $metaTemplate) {
            if ($metaTemplate['name'] === $template->template_name && $metaTemplate['language'] === $template->language_code) {
                return $metaTemplate;
            }
        }

        return null;
    }

    /**
     * Inactive mappings are never sent, so they are saved without checking them against Meta.
     *
     * @param array{template_name: string, language_code: string, parameters: list<string>, is_active: bool} $data
     * @param list<MetaTemplate>                                                                             $metaTemplates the result of syncFromMeta()
     *
     * @throws ValidationException
     */
    public function update(WhatsappTemplate $template, array $data, array $metaTemplates): WhatsappTemplate {
        $template->fill($data);

        if ($template->is_active) {
            $problems = $this->validateMapping($template, $metaTemplates);

            if ($problems !== []) {
                throw ValidationException::withMessages(['template_name' => $problems]);
            }
        }

        $template->save();

        return $template;
    }

    /**
     * @param list<array<string, mixed>> $components
     */
    private function countBodyPlaceholders(array $components): int {
        foreach ($components as $component) {
            if (($component['type'] ?? null) === 'BODY') {
                preg_match_all('/\{\{\s*(\d+)\s*\}\}/', (string) ($component['text'] ?? ''), $matches);

                return count(array_unique($matches[1]));
            }
        }

        return 0;
    }
}
