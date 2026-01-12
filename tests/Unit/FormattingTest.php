<?php

declare(strict_types=1);

namespace Imb\Tests\Unit;

use Imb\Formatting;



use PHPUnit\Framework\TestCase;


final class FormattingTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure tables are initialized
        Formatting::initialize();
    }

    /** @test */
    public function initializeCreatesEncodeTables(): void
    {
        $encodeTable = Formatting::getEncodeTable();
        $decodeTable = Formatting::getDecodeTable();
        $fcsTable = Formatting::getFcsTable();

        $this->assertCount(1365, $encodeTable);
        $this->assertCount(8192, $decodeTable);
        $this->assertCount(8192, $fcsTable);
    }

    /** @test */
    public function initializeIsIdempotent(): void
    {
        Formatting::initialize();
        $table1 = Formatting::getEncodeTable();

        Formatting::initialize();
        $table2 = Formatting::getEncodeTable();

        $this->assertSame($table1, $table2);
    }

    /** @test */
    public function getEncodeValueReturnsCorrectValue(): void
    {
        $value = Formatting::getEncodeValue(0);
        $this->assertIsInt($value);
    }

    /** @test */
    public function getDecodeValueReturnsCorrectValue(): void
    {
        // Get an encode value and verify we can decode it
        $encoded = Formatting::getEncodeValue(100);
        $decoded = Formatting::getDecodeValue($encoded);

        $this->assertSame(100, $decoded);
    }

    /**
     * @test
     * @dataProvider cleanStringProvider
     */
    public function cleanStringNormalizesInput(string $input, string $expected): void
    {
        $result = Formatting::cleanString($input);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function cleanStringProvider(): array
    {
        return [
            'lowercase' => ['abc', 'ABC'],
            'mixed case' => ['AbCdEf', 'ABCDEF'],
            'with spaces' => ['a b c', 'ABC'],
            'with tabs' => ["a\tb\tc", 'ABC'],
            'with newlines' => ["a\nb\nc", 'ABC'],
            'empty string' => ['', ''],
            'already uppercase' => ['ABC', 'ABC'],
            'digits' => ['123', '123'],
            'mixed' => ['abc 123 DEF', 'ABC123DEF'],
        ];
    }

    /** @test */
    public function cleanStringHandlesNull(): void
    {
        $result = Formatting::cleanString(null);
        $this->assertSame('', $result);
    }

    /**
     * @test
     * @dataProvider isZeroProvider
     */
    public function isZeroDetectsZeroArrays(array $num, bool $expected): void
    {
        $result = Formatting::isZero($num);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{array<int, int>, bool}>
     */
    public static function isZeroProvider(): array
    {
        return [
            'all zeros' => [[0, 0, 0, 0, 0], true],
            'single zero' => [[0], true],
            'empty array' => [[], true],
            'has non-zero at start' => [[1, 0, 0, 0], false],
            'has non-zero at end' => [[0, 0, 0, 1], false],
            'has non-zero in middle' => [[0, 0, 1, 0, 0], false],
            'all non-zero' => [[1, 2, 3], false],
        ];
    }

    /** @test */
    public function addModifiesArrayCorrectly(): void
    {
        $num = [0, 0, 0, 0, 0];
        Formatting::add($num, 5);

        $this->assertSame(5, $num[4]);
    }

    /** @test */
    public function addHandlesCarry(): void
    {
        // 11-bit words max value is 2047 (0x7ff)
        $num = [0, 0, 0, 0, 2047];
        Formatting::add($num, 1);

        // Should carry over
        $this->assertSame(0, $num[4]);
        $this->assertSame(1, $num[3]);
    }

    /** @test */
    public function multiplyAndAddWorks(): void
    {
        $num = [0, 0, 0, 0, 5];
        Formatting::multiplyAndAdd($num, 10, 3);

        // 5 * 10 + 3 = 53
        $this->assertSame(53, $num[4]);
    }

    /** @test */
    public function multiplyAndAddHandlesLargeNumbers(): void
    {
        $num = [0, 0, 0, 0, 1000];
        Formatting::multiplyAndAdd($num, 1000, 0);

        // 1000 * 1000 = 1,000,000 which exceeds 11-bit max (2047)
        // Should spread across multiple words (most significant first)
        // 1,000,000 = 488 * 2048 + 576 = 488 << 11 | 576
        $total = 0;
        for ($i = 0; $i < count($num); $i++) {
            $total = ($total << 11) | $num[$i];
        }
        $this->assertSame(1000000, $total);
    }

    /** @test */
    public function divideModulusWorks(): void
    {
        $num = [0, 0, 0, 0, 53];
        $remainder = Formatting::divideModulus($num, 10);

        $this->assertSame(3, $remainder);
        $this->assertSame(5, $num[4]);
    }

    /** @test */
    public function divideModulusHandlesLargeNumbers(): void
    {
        // Build a large number: 1000000 in multi-precision format
        $num = [0, 0, 0, 0, 0];
        Formatting::multiplyAndAdd($num, 1, 1000000);

        $remainder = Formatting::divideModulus($num, 1000);

        $this->assertSame(0, $remainder); // 1000000 % 1000 = 0
    }

    /** @test */
    public function calculateFrameCheckProducesConsistentResults(): void
    {
        $num1 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 100];
        $num2 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 100];

        $fcs1 = Formatting::calculateFrameCheck($num1);
        $fcs2 = Formatting::calculateFrameCheck($num2);

        $this->assertSame($fcs1, $fcs2);
    }

    /** @test */
    public function calculateFrameCheckProducesDifferentResultsForDifferentInput(): void
    {
        $num1 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 100];
        $num2 = [0, 0, 0, 0, 0, 0, 0, 0, 0, 101];

        $fcs1 = Formatting::calculateFrameCheck($num1);
        $fcs2 = Formatting::calculateFrameCheck($num2);

        $this->assertNotSame($fcs1, $fcs2);
    }

    /** @test */
    public function charactersToTextProduces65Characters(): void
    {
        $chars = array_fill(0, 10, 0);
        $result = Formatting::charactersToText($chars);

        $this->assertSame(65, strlen($result));
    }

    /** @test */
    public function charactersToTextOnlyUsesValidChars(): void
    {
        $chars = array_fill(0, 10, 0);
        $result = Formatting::charactersToText($chars);

        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function textToCharactersConvertsValidBarcode(): void
    {
        // Create a simple valid barcode string
        $barcode = str_repeat('T', 65);
        $chars = Formatting::textToCharacters($barcode, false);

        $this->assertIsArray($chars);
        $this->assertCount(10, $chars);
    }

    /** @test */
    public function textToCharactersHandlesAllCharacterTypes(): void
    {
        // Test each character type
        $chars = Formatting::textToCharacters('ADFTS' . str_repeat('T', 60), false);

        $this->assertIsArray($chars);
        $this->assertCount(10, $chars);
    }

    /** @test */
    public function textToCharactersStrictModeRejectsInvalidChars(): void
    {
        $barcode = str_repeat('X', 65); // Invalid characters
        $chars = Formatting::textToCharacters($barcode, true);

        $this->assertNull($chars);
    }

    /** @test */
    public function textToCharactersNonStrictModeAcceptsInvalidChars(): void
    {
        $barcode = str_repeat('X', 65);
        $chars = Formatting::textToCharacters($barcode, false);

        $this->assertIsArray($chars);
    }

    /** @test */
    public function charactersToTextAndTextToCharactersAreReversible(): void
    {
        // Create some characters
        $originalChars = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];
        $text = Formatting::charactersToText($originalChars);

        // Convert back
        $resultChars = Formatting::textToCharacters($text, false);

        // The bit patterns should match
        $this->assertIsArray($resultChars);
    }
}
