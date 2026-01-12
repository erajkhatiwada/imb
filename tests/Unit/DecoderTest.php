<?php

declare(strict_types=1);

namespace Imb\Tests\Unit;

use Imb\Decoder;
use Imb\DecodeResult;
use Imb\Encoder;
use Imb\Exception\DecodingException;
use PHPUnit\Framework\TestCase;

final class DecoderTest extends TestCase
{
    private Decoder $decoder;
    private Encoder $encoder;

    protected function setUp(): void
    {
        $this->decoder = new Decoder();
        $this->encoder = new Encoder();
    }

    /** @test */
    public function decodeValidBarcodeWithoutRouting(): void
    {
        // First encode a known value
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertInstanceOf(DecodeResult::class, $result);
        $this->assertSame('01', $result->data->barcodeId);
        $this->assertSame('234', $result->data->serviceType);
        $this->assertSame('567094', $result->data->mailerId);
        $this->assertSame('987654321', $result->data->serialNum);
        $this->assertNull($result->data->zip);
        $this->assertNull($result->data->plus4);
        $this->assertNull($result->data->deliveryPt);
        $this->assertNull($result->message);
    }

    /** @test */
    public function decodeValidBarcodeWithZipCode(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertSame('01', $result->data->barcodeId);
        $this->assertSame('234', $result->data->serviceType);
        $this->assertSame('567094', $result->data->mailerId);
        $this->assertSame('987654321', $result->data->serialNum);
        $this->assertSame('12345', $result->data->zip);
        $this->assertNull($result->data->plus4);
        $this->assertNull($result->data->deliveryPt);
    }

    /** @test */
    public function decodeValidBarcodeWithZipAndPlus4(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '6789',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertSame('12345', $result->data->zip);
        $this->assertSame('6789', $result->data->plus4);
        $this->assertNull($result->data->deliveryPt);
    }

    /** @test */
    public function decodeValidBarcodeWithFullRouting(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '6789',
            'delivery_pt' => '01',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertSame('12345', $result->data->zip);
        $this->assertSame('6789', $result->data->plus4);
        $this->assertSame('01', $result->data->deliveryPt);
    }

    /** @test */
    public function decodeValidBarcodeWithNineDigitMailerId(): void
    {
        // 9-digit mailer IDs must start with digit 9 per USPS specification
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '901234567',
            'serial_num' => '012345',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertSame('901234567', $result->data->mailerId);
        $this->assertSame('012345', $result->data->serialNum);
    }

    /** @test */
    public function decodeHandlesLowercaseInput(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $barcode = strtolower($this->encoder->encode($originalData));
        $result = $this->decoder->decode($barcode);

        $this->assertSame('01', $result->data->barcodeId);
    }

    /** @test */
    public function decodeHandlesWhitespaceInInput(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $barcode = $this->encoder->encode($originalData);
        // Add some whitespace
        $barcodeWithSpaces = substr($barcode, 0, 20) . ' ' . substr($barcode, 20, 20) . '  ' . substr($barcode, 40);
        // Note: This will strip whitespace, so we need exactly 65 non-space chars
        // Let's just add spaces around instead
        $barcodeWithSpaces = '  ' . $barcode . '  ';

        $result = $this->decoder->decode($barcodeWithSpaces);

        $this->assertSame('01', $result->data->barcodeId);
    }

    /** @test */
    public function decodeThrowsExceptionForTooShortBarcode(): void
    {
        $this->expectException(DecodingException::class);
        $this->expectExceptionMessage('Barcode must be 65 characters long');

        $this->decoder->decode('ADFTA'); // Too short
    }

    /** @test */
    public function decodeThrowsExceptionForTooLongBarcode(): void
    {
        $this->expectException(DecodingException::class);

        // Create a 70 character barcode
        $this->decoder->decode(str_repeat('A', 70));
    }

    /** @test */
    public function decodeThrowsExceptionForInvalidCharacters(): void
    {
        $this->expectException(DecodingException::class);

        // 65 characters but with invalid character 'X'
        $this->decoder->decode(str_repeat('X', 65));
    }

    /** @test */
    public function decodeThrowsExceptionForCompletelyInvalidBarcode(): void
    {
        $this->expectException(DecodingException::class);

        // Valid characters but invalid barcode
        $this->decoder->decode(str_repeat('A', 65));
    }

    /** @test */
    public function decodeReturnsDecodeResultObject(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertInstanceOf(DecodeResult::class, $result);
        $this->assertFalse($result->wasDamaged());
    }

    /** @test */
    public function decodeResultToArrayWorks(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
        ];

        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertSame('01', $array['barcode_id']);
        $this->assertSame('234', $array['service_type']);
        $this->assertSame('567094', $array['mailer_id']);
        $this->assertSame('987654321', $array['serial_num']);
        $this->assertSame('12345', $array['zip']);
    }

    /**
     * @test
     * @dataProvider roundTripDataProvider
     */
    public function decodeRoundTrip(array $originalData): void
    {
        $barcode = $this->encoder->encode($originalData);
        $result = $this->decoder->decode($barcode);

        $this->assertSame($originalData['barcode_id'], $result->data->barcodeId);
        $this->assertSame($originalData['service_type'], $result->data->serviceType);
        $this->assertSame($originalData['mailer_id'], $result->data->mailerId);
        $this->assertSame($originalData['serial_num'], $result->data->serialNum);

        if (isset($originalData['zip'])) {
            $this->assertSame($originalData['zip'], $result->data->zip);
        }
        if (isset($originalData['plus4'])) {
            $this->assertSame($originalData['plus4'], $result->data->plus4);
        }
        if (isset($originalData['delivery_pt'])) {
            $this->assertSame($originalData['delivery_pt'], $result->data->deliveryPt);
        }
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function roundTripDataProvider(): array
    {
        return [
            'basic' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
            ]],
            'with zip' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
                'zip' => '12345',
            ]],
            'with zip and plus4' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
                'zip' => '12345',
                'plus4' => '6789',
            ]],
            'full routing' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
                'zip' => '12345',
                'plus4' => '6789',
                'delivery_pt' => '01',
            ]],
            'nine digit mailer' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '901234567',  // 9-digit mailer IDs must start with 9
                'serial_num' => '012345',
            ]],
            'all zeros' => [[
                'barcode_id' => '00',
                'service_type' => '000',
                'mailer_id' => '000000',
                'serial_num' => '000000000',
            ]],
            'mixed values' => [[
                'barcode_id' => '24',
                'service_type' => '456',
                'mailer_id' => '789012',
                'serial_num' => '345678901',
                'zip' => '54321',
            ]],
        ];
    }

    /** @test */
    public function decodeSupportsTCharacter(): void
    {
        $originalData = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $barcode = $this->encoder->encode($originalData);

        // Verify T characters are valid
        $this->assertStringContainsString('T', $barcode);

        $result = $this->decoder->decode($barcode);
        $this->assertSame('01', $result->data->barcodeId);
    }

    /** @test */
    public function decodeMultipleDifferentBarcodes(): void
    {
        // Note: 6-digit mailer IDs cannot start with 9 (those are 9-digit mailer IDs)
        $testCases = [
            ['barcode_id' => '00', 'service_type' => '100', 'mailer_id' => '111111', 'serial_num' => '222222222'],
            ['barcode_id' => '01', 'service_type' => '200', 'mailer_id' => '333333', 'serial_num' => '444444444'],
            ['barcode_id' => '02', 'service_type' => '300', 'mailer_id' => '555555', 'serial_num' => '666666666'],
            ['barcode_id' => '03', 'service_type' => '400', 'mailer_id' => '777777', 'serial_num' => '888888888'],
            ['barcode_id' => '04', 'service_type' => '500', 'mailer_id' => '888888', 'serial_num' => '000000000'],
        ];

        foreach ($testCases as $originalData) {
            $barcode = $this->encoder->encode($originalData);
            $result = $this->decoder->decode($barcode);

            $this->assertSame($originalData['barcode_id'], $result->data->barcodeId);
            $this->assertSame($originalData['service_type'], $result->data->serviceType);
            $this->assertSame($originalData['mailer_id'], $result->data->mailerId);
            $this->assertSame($originalData['serial_num'], $result->data->serialNum);
        }
    }
}
