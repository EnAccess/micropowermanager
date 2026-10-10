<?php

namespace App\Plugins\WhatsappMessaging\Services;

final readonly class WhatsappOutboundMessage {
    /**
     * @param string        $to                 recipient phone number in E.164 format
     * @param string        $body               rendered text; sent as-is for free-text messages and kept for logs on template messages
     * @param array<string> $templateParameters ordered values for the template body placeholders {{1}}, {{2}}, ...
     */
    public function __construct(
        public string $to,
        public string $body,
        public ?string $templateName = null,
        public ?string $templateLanguage = null,
        public array $templateParameters = [],
    ) {}

    public function isTemplate(): bool {
        return $this->templateName !== null;
    }
}
