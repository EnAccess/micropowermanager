<?php

namespace Tests\Unit;

use App\Helpers\PhoneNumberNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberNormalizerTest extends TestCase {
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function phoneNumberProvider(): array {
        return [
            'null returns null' => [null, null],
            'empty string returns null' => ['', null],
            'whitespace only returns null' => ['   ', null],
            'already canonical' => ['+123456789', '+123456789'],
            'strips spaces' => ['+1 234 567 89', '+123456789'],
            'strips hyphens' => ['+1-234-567-89', '+123456789'],
            'strips parentheses' => ['+(1)234567', '+1234567'],
            'strips dots' => ['+1.234.567.89', '+123456789'],
            'mixed formatting' => ['+1 (234) 567-89', '+123456789'],
            'no plus prefix adds one' => ['123456789', '+123456789'],
            'no plus with spaces' => ['1 234 567 89', '+123456789'],
            'leading/trailing whitespace trimmed' => ['  +123456789  ', '+123456789'],
        ];
    }

    #[DataProvider('phoneNumberProvider')]
    public function testNormalize(?string $input, ?string $expected): void {
        $this->assertSame($expected, PhoneNumberNormalizer::normalize($input));
    }
}
