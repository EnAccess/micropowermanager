<?php

namespace App\Plugins\AirtelSmsGateway\Smpp;

/**
 * Converts text to the GSM 03.38 default alphabet, the character set SMPP calls "SMSC default".
 * Each character is one octet; characters of the extension table are two (escape + character).
 */
class GsmAlphabet {
    public const string ESCAPE = "\x1B";

    /** Characters whose GSM 03.38 code differs from their ASCII code. */
    private const array BASIC_TABLE = [
        '@' => "\x00", '£' => "\x01", '$' => "\x02", '¥' => "\x03", 'è' => "\x04", 'é' => "\x05", 'ù' => "\x06", 'ì' => "\x07",
        'ò' => "\x08", 'Ç' => "\x09", 'Ø' => "\x0B", 'ø' => "\x0C", 'Å' => "\x0E", 'å' => "\x0F",
        'Δ' => "\x10", '_' => "\x11", 'Φ' => "\x12", 'Γ' => "\x13", 'Λ' => "\x14", 'Ω' => "\x15", 'Π' => "\x16", 'Ψ' => "\x17",
        'Σ' => "\x18", 'Θ' => "\x19", 'Ξ' => "\x1A", 'Æ' => "\x1C", 'æ' => "\x1D", 'ß' => "\x1E", 'É' => "\x1F",
        '¤' => "\x24", '¡' => "\x40",
        'Ä' => "\x5B", 'Ö' => "\x5C", 'Ñ' => "\x5D", 'Ü' => "\x5E", '§' => "\x5F", '¿' => "\x60",
        'ä' => "\x7B", 'ö' => "\x7C", 'ñ' => "\x7D", 'ü' => "\x7E", 'à' => "\x7F",
    ];

    private const array EXTENSION_TABLE = [
        '^' => "\x1B\x14", '{' => "\x1B\x28", '}' => "\x1B\x29", '\\' => "\x1B\x2F", '[' => "\x1B\x3C",
        '~' => "\x1B\x3D", ']' => "\x1B\x3E", '|' => "\x1B\x40", '€' => "\x1B\x65",
    ];

    private const string REPLACEMENT = '?';

    public static function encode(string $text): string {
        $encoded = '';

        foreach (mb_str_split($text, 1, 'UTF-8') as $character) {
            $encoded .= self::EXTENSION_TABLE[$character]
                ?? self::BASIC_TABLE[$character]
                ?? (self::isSharedWithAscii($character) ? $character : self::REPLACEMENT);
        }

        return $encoded;
    }

    private static function isSharedWithAscii(string $character): bool {
        if (strlen($character) !== 1) {
            return false;
        }

        return $character === "\n" || $character === "\r" || ($character >= ' ' && $character <= 'z' && $character !== '`');
    }
}
