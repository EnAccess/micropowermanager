<?php

namespace App\Plugins\WhatsappMessaging\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * A template the admin created in WhatsApp Manager, as Meta last reported it.
 *
 * @property int                        $id
 * @property string                     $meta_template_id
 * @property string                     $name
 * @property string                     $language
 * @property string                     $category
 * @property string                     $status
 * @property string                     $parameter_format
 * @property list<array<string, mixed>> $components
 * @property string|null                $rejected_reason
 * @property string|null                $rejection_detail
 * @property string|null                $rejection_recommendation
 * @property string|null                $status_detail
 * @property Carbon|null                $created_at
 * @property Carbon|null                $updated_at
 */
class WhatsappTemplate extends BaseModel {
    public const STATUS_APPROVED = 'APPROVED';

    public const PARAMETER_FORMAT_NAMED = 'NAMED';
    public const PARAMETER_FORMAT_POSITIONAL = 'POSITIONAL';

    private const string PARAMETER_PATTERN = '/{{\s*(\w+)\s*}}/';

    protected $table = 'whatsapp_templates';

    protected function casts(): array {
        return ['components' => 'array'];
    }

    /** @return list<string> each parameter of the header and body once, in the order it first appears */
    public function parameterNames(): array {
        preg_match_all(self::PARAMETER_PATTERN, $this->text('HEADER').' '.$this->text('BODY'), $matches);

        return array_values(array_unique($matches[1]));
    }

    /** False when sending needs more than text values: a media header, or a button whose URL holds a variable. */
    public function canBeFilledWithText(): bool {
        $header = $this->component('HEADER');
        if ($header !== null && ($header['format'] ?? 'TEXT') !== 'TEXT') {
            return false;
        }

        foreach ($this->component('BUTTONS')['buttons'] ?? [] as $button) {
            if (preg_match(self::PARAMETER_PATTERN, (string) ($button['url'] ?? '')) === 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int|string, string> $values keyed by parameter name; a positional template's names are 1, 2, …
     *
     * @return array<string, mixed> the message content that sends this template
     */
    public function message(array $values): array {
        $components = [];
        foreach (['HEADER' => 'header', 'BODY' => 'body'] as $type => $requestType) {
            preg_match_all(self::PARAMETER_PATTERN, $this->text($type), $matches);
            $names = array_values(array_unique($matches[1]));
            if ($names === []) {
                continue;
            }

            $isNamed = $this->parameter_format === self::PARAMETER_FORMAT_NAMED;
            // Meta matches positional parameters by their place in the list, not by the number written in the text.
            if (!$isNamed) {
                sort($names, SORT_NUMERIC);
            }

            $components[] = ['type' => $requestType, 'parameters' => array_map(fn (string $name): array => [
                'type' => 'text',
                ...($isNamed ? ['parameter_name' => $name] : []),
                'text' => $this->parameterValue($values[$name] ?? ''),
            ], $names)];
        }

        return ['type' => 'template', 'template' => ['name' => $this->name, 'language' => ['code' => $this->language], 'components' => $components]];
    }

    /**
     * The words the customer reads, which is also what the SMS fallback sends, so both channels say the same.
     *
     * @param array<int|string, string> $values
     */
    public function render(array $values): string {
        $fill = fn (string $text): string => (string) preg_replace_callback(self::PARAMETER_PATTERN, fn (array $match): string => $this->parameterValue($values[$match[1]] ?? ''), $text);

        return implode("\n", array_filter([$fill($this->text('HEADER')), $fill($this->text('BODY'))]));
    }

    public function text(string $type): string {
        return (string) ($this->component($type)['text'] ?? '');
    }

    // Meta rejects parameters that are empty or contain newlines, tabs or runs of spaces.
    private function parameterValue(string $value): string {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? '-' : $value;
    }

    /** @return array<string, mixed>|null */
    private function component(string $type): ?array {
        foreach ($this->components as $component) {
            if (($component['type'] ?? null) === $type) {
                return $component;
            }
        }

        return null;
    }
}
