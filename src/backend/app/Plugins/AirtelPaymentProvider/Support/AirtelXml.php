<?php

namespace App\Plugins\AirtelPaymentProvider\Support;

use App\Plugins\AirtelPaymentProvider\Exceptions\AirtelRequestException;
use Illuminate\Http\Response;

/**
 * Airtel sends and expects flat `<COMMAND>` documents. It reads the outcome from the STATUS
 * element, so every answer is sent with HTTP 200.
 */
class AirtelXml {
    /**
     * @return array<string, string>
     */
    public static function parse(string $body): array {
        $previousSetting = libxml_use_internal_errors(true);
        $command = simplexml_load_string($body, options: LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        if ($command === false) {
            throw new AirtelRequestException('Request body is not valid XML');
        }

        $fields = [];
        foreach ($command->children() as $name => $value) {
            $fields[$name] = trim((string) $value);
        }

        return $fields;
    }

    /**
     * @param array<string, string|int> $extraFields
     */
    public static function response(int $status, string $message, array $extraFields = []): Response {
        $command = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><COMMAND/>');
        foreach (['STATUS' => $status, 'MESSAGE' => $message, ...$extraFields] as $name => $value) {
            // The two-argument addChild() does not escape "&", assigning the value does.
            $command->addChild($name)[0] = (string) $value;
        }

        return new Response((string) $command->asXML(), 200, ['Content-Type' => 'application/xml']);
    }
}
