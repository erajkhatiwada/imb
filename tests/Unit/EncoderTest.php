<?php

declare(strict_types=1);

namespace Imb\Tests\Unit;

use Imb\Encoder;
use Imb\Exception\ValidationException;
use Imb\IMBData;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Imb\Encoder
 */
final class EncoderTest extends TestCase
{
    private Encoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new Encoder();
    }

    /**
     * @test
     */
    public function encodeBasicBarcodeWithoutRouting(): void
    {
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeWithZipCode(): void
    {
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeWithZipAndPlus4(): void
    {
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '6789',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeWithFullRouting(): void
    {
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '6789',
            'delivery_pt' => '01',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeWithNineDigitMailerId(): void
    {
        // 9-digit mailer IDs must start with digit 9 per USPS specification
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '901234567',
            'serial_num' => '012345',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeWithIMBDataObject(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeHandlesWhitespaceInInput(): void
    {
        $data = [
            'barcode_id' => ' 01 ',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
    }

    /** @test */
    public function encodeHandlesLowercaseInput(): void
    {
        // While input is typically digits, test case-insensitivity
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidZipCode(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Zip code must be 5 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '1234', // 4 digits instead of 5
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForNonNumericZipCode(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Zip code must be 5 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '1234A',
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForPlus4WithoutZip(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Zip code is required if plus4 is provided');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'plus4' => '6789',
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidPlus4(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('plus4 must be 4 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '123', // 3 digits instead of 4
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidDeliveryPoint(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Delivery Point must be 2 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'delivery_pt' => '1', // 1 digit instead of 2
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidBarcodeId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Barcode ID must be 2 digits');

        $this->encoder->encode([
            'barcode_id' => '1', // 1 digit instead of 2
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForBarcodeIdSecondDigitTooHigh(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Second digit of Barcode ID must be 0-4');

        $this->encoder->encode([
            'barcode_id' => '05', // Second digit >= 5
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidServiceType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Service Type must be 3 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '12', // 2 digits instead of 3
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidMailerId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mailer ID must be 6 or 9 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '12345', // 5 digits
            'serial_num' => '987654321',
        ]);
    }

    /** @test */
    public function encodeThrowsExceptionForInvalidSerialNumLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mailer ID and Serial Number together must be 15 digits');

        $this->encoder->encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094', // 6 digits
            'serial_num' => '12345678', // 8 digits (should be 9)
        ]);
    }

    /**
     * @test
     * @dataProvider validBarcodeIdSecondDigitsProvider
     */
    public function encodeAcceptsValidBarcodeIdSecondDigits(string $barcodeId): void
    {
        $result = $this->encoder->encode([
            'barcode_id' => $barcodeId,
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $this->assertSame(65, strlen($result));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validBarcodeIdSecondDigitsProvider(): array
    {
        return [
            'second digit 0' => ['00'],
            'second digit 1' => ['01'],
            'second digit 2' => ['02'],
            'second digit 3' => ['03'],
            'second digit 4' => ['04'],
            'first digit varies' => ['90'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidBarcodeIdSecondDigitsProvider
     */
    public function encodeRejectsInvalidBarcodeIdSecondDigits(string $barcodeId): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Second digit of Barcode ID must be 0-4');

        $this->encoder->encode([
            'barcode_id' => $barcodeId,
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidBarcodeIdSecondDigitsProvider(): array
    {
        return [
            'second digit 5' => ['05'],
            'second digit 6' => ['06'],
            'second digit 7' => ['07'],
            'second digit 8' => ['08'],
            'second digit 9' => ['09'],
        ];
    }

    /** @test */
    public function encodeProducesDeterministicOutput(): void
    {
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
        ];

        $result1 = $this->encoder->encode($data);
        $result2 = $this->encoder->encode($data);

        $this->assertSame($result1, $result2);
    }

    /** @test */
    public function encodeProducesDifferentOutputForDifferentInput(): void
    {
        $data1 = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ];

        $data2 = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654322', // Different serial
        ];

        $result1 = $this->encoder->encode($data1);
        $result2 = $this->encoder->encode($data2);

        $this->assertNotSame($result1, $result2);
    }

    /** @test */
    public function encodeWithAllZeros(): void
    {
        $data = [
            'barcode_id' => '00',
            'service_type' => '000',
            'mailer_id' => '000000',
            'serial_num' => '000000000',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function encodeWithMaxValues(): void
    {
        $data = [
            'barcode_id' => '94', // Max for second digit is 4
            'service_type' => '999',
            'mailer_id' => '999999',
            'serial_num' => '999999999',
            'zip' => '99999',
            'plus4' => '9999',
            'delivery_pt' => '99',
        ];

        $result = $this->encoder->encode($data);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }
}
