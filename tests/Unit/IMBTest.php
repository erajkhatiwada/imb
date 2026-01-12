<?php

declare(strict_types=1);

namespace Imb\Tests\Unit;

use Imb\Decoder;
use Imb\DecodeResult;
use Imb\Encoder;
use Imb\Exception\DecodingException;
use Imb\Exception\ValidationException;
use Imb\IMB;
use Imb\IMBData;


use PHPUnit\Framework\TestCase;


final class IMBTest extends TestCase
{
    /** @test */
    public function encodeStaticMethodWorks(): void
    {
        $result = IMB::encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $this->assertSame(65, strlen($result));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $result);
    }

    /** @test */
    public function decodeStaticMethodWorks(): void
    {
        $barcode = IMB::encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $result = IMB::decode($barcode);

        $this->assertInstanceOf(DecodeResult::class, $result);
        $this->assertSame('01', $result->data->barcodeId);
    }

    /** @test */
    public function decodeToArrayStaticMethodWorks(): void
    {
        $barcode = IMB::encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $result = IMB::decodeToArray($barcode);

        $this->assertIsArray($result);
        $this->assertSame('01', $result['barcode_id']);
        $this->assertSame('234', $result['service_type']);
        $this->assertSame('567094', $result['mailer_id']);
        $this->assertSame('987654321', $result['serial_num']);
    }

    /** @test */
    public function validateReturnsTrueForValidData(): void
    {
        $result = IMB::validate([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function validateReturnsFalseForInvalidData(): void
    {
        $result = IMB::validate([
            'barcode_id' => '99', // Invalid: second digit must be 0-4
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $this->assertFalse($result);
    }

    /** @test */
    public function validateBarcodeReturnsTrueForValidBarcode(): void
    {
        $barcode = IMB::encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $result = IMB::validateBarcode($barcode);

        $this->assertTrue($result);
    }

    /** @test */
    public function validateBarcodeReturnsFalseForInvalidBarcode(): void
    {
        $result = IMB::validateBarcode('INVALID');

        $this->assertFalse($result);
    }

    /** @test */
    public function createDataReturnsIMBDataObject(): void
    {
        $data = IMB::createData([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $this->assertInstanceOf(IMBData::class, $data);
        $this->assertSame('01', $data->barcodeId);
    }

    /** @test */
    public function getEncoderReturnsSameInstance(): void
    {
        $encoder1 = IMB::getEncoder();
        $encoder2 = IMB::getEncoder();

        $this->assertSame($encoder1, $encoder2);
        $this->assertInstanceOf(Encoder::class, $encoder1);
    }

    /** @test */
    public function getDecoderReturnsSameInstance(): void
    {
        $decoder1 = IMB::getDecoder();
        $decoder2 = IMB::getDecoder();

        $this->assertSame($decoder1, $decoder2);
        $this->assertInstanceOf(Decoder::class, $decoder1);
    }

    /** @test */
    public function encodeThrowsValidationExceptionForInvalidData(): void
    {
        $this->expectException(ValidationException::class);

        IMB::encode([
            'barcode_id' => 'XX', // Invalid
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);
    }

    /** @test */
    public function decodeThrowsDecodingExceptionForInvalidBarcode(): void
    {
        $this->expectException(DecodingException::class);

        IMB::decode('INVALID');
    }

    /** @test */
    public function encodeAcceptsIMBDataObject(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $result = IMB::encode($data);

        $this->assertSame(65, strlen($result));
    }

    /** @test */
    public function fullRoundTrip(): void
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

        $barcode = IMB::encode($originalData);
        $decoded = IMB::decodeToArray($barcode);

        $this->assertSame($originalData['barcode_id'], $decoded['barcode_id']);
        $this->assertSame($originalData['service_type'], $decoded['service_type']);
        $this->assertSame($originalData['mailer_id'], $decoded['mailer_id']);
        $this->assertSame($originalData['serial_num'], $decoded['serial_num']);
        $this->assertSame($originalData['zip'], $decoded['zip']);
        $this->assertSame($originalData['plus4'], $decoded['plus4']);
        $this->assertSame($originalData['delivery_pt'], $decoded['delivery_pt']);
    }
}
