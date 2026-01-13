<?php

declare(strict_types=1);

namespace Imb\Tests\Unit;

use Imb\IMBData;


use PHPUnit\Framework\TestCase;


final class IMBDataTest extends TestCase
{
    /** @test */
    public function constructorSetsProperties(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321',
            zip: '12345',
            plus4: '6789',
            deliveryPt: '01'
        );

        $this->assertSame('01', $data->barcodeId);
        $this->assertSame('234', $data->serviceType);
        $this->assertSame('567094', $data->mailerId);
        $this->assertSame('987654321', $data->serialNum);
        $this->assertSame('12345', $data->zip);
        $this->assertSame('6789', $data->plus4);
        $this->assertSame('01', $data->deliveryPt);
    }

    /** @test */
    public function constructorAllowsNullOptionalFields(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $this->assertNull($data->zip);
        $this->assertNull($data->plus4);
        $this->assertNull($data->deliveryPt);
    }

    /** @test */
    public function fromArrayWithSnakeCaseKeys(): void
    {
        $data = IMBData::fromArray([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '6789',
            'delivery_pt' => '01',
        ]);

        $this->assertSame('01', $data->barcodeId);
        $this->assertSame('234', $data->serviceType);
        $this->assertSame('567094', $data->mailerId);
        $this->assertSame('987654321', $data->serialNum);
        $this->assertSame('12345', $data->zip);
        $this->assertSame('6789', $data->plus4);
        $this->assertSame('01', $data->deliveryPt);
    }

    /** @test */
    public function fromArrayWithCamelCaseKeys(): void
    {
        $data = IMBData::fromArray([
            'barcodeId' => '01',
            'serviceType' => '234',
            'mailerId' => '567094',
            'serialNum' => '987654321',
            'deliveryPt' => '01',
        ]);

        $this->assertSame('01', $data->barcodeId);
        $this->assertSame('234', $data->serviceType);
        $this->assertSame('567094', $data->mailerId);
        $this->assertSame('987654321', $data->serialNum);
        $this->assertSame('01', $data->deliveryPt);
    }

    /** @test */
    public function fromArrayHandlesMissingOptionalFields(): void
    {
        $data = IMBData::fromArray([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        $this->assertNull($data->zip);
        $this->assertNull($data->plus4);
        $this->assertNull($data->deliveryPt);
    }

    /** @test */
    public function toArrayReturnsCorrectFormat(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321',
            zip: '12345',
            plus4: '6789',
            deliveryPt: '01'
        );

        $array = $data->toArray();

        $this->assertSame('01', $array['barcode_id']);
        $this->assertSame('234', $array['service_type']);
        $this->assertSame('567094', $array['mailer_id']);
        $this->assertSame('987654321', $array['serial_num']);
        $this->assertSame('12345', $array['zip']);
        $this->assertSame('6789', $array['plus4']);
        $this->assertSame('01', $array['delivery_pt']);
    }

    /** @test */
    public function toArrayOmitsNullOptionalFields(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $array = $data->toArray();

        $this->assertArrayHasKey('barcode_id', $array);
        $this->assertArrayHasKey('service_type', $array);
        $this->assertArrayHasKey('mailer_id', $array);
        $this->assertArrayHasKey('serial_num', $array);
        $this->assertArrayNotHasKey('zip', $array);
        $this->assertArrayNotHasKey('plus4', $array);
        $this->assertArrayNotHasKey('delivery_pt', $array);
    }

    /** @test */
    public function getMailerIdLengthReturns6ForSixDigitMailerId(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $this->assertSame(6, $data->getMailerIdLength());
    }

    /** @test */
    public function getMailerIdLengthReturns9ForNineDigitMailerId(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '123456789',
            serialNum: '012345'
        );

        $this->assertSame(9, $data->getMailerIdLength());
    }

    /** @test */
    public function hasNineDigitMailerIdReturnsTrueForNineDigits(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '123456789',
            serialNum: '012345'
        );

        $this->assertTrue($data->hasNineDigitMailerId());
    }

    /** @test */
    public function hasNineDigitMailerIdReturnsFalseForSixDigits(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $this->assertFalse($data->hasNineDigitMailerId());
    }

    /** @test */
    public function fromArrayAndToArrayAreReversible(): void
    {
        $original = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
            'plus4' => '6789',
            'delivery_pt' => '01',
        ];

        $data = IMBData::fromArray($original);
        $result = $data->toArray();

        $this->assertSame($original, $result);
    }

    /** @test */
    public function stringifyReturnsConcatenatedFieldsWithAllFields(): void
    {
        $data = new IMBData(
            barcodeId: '00',
            serviceType: '270',
            mailerId: '103502',
            serialNum: '017955971',
            zip: '50310',
            plus4: '1605',
            deliveryPt: '15'
        );

        $result = $data->stringify();

        $this->assertSame('0027010350201795597150310160515', $result);
    }

    /** @test */
    public function stringifyReturnsConcatenatedFieldsWithoutOptionalFields(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321'
        );

        $result = $data->stringify();

        $this->assertSame('01234567094987654321', $result);
    }

    /** @test */
    public function stringifyWithPartialOptionalFields(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '567094',
            serialNum: '987654321',
            zip: '12345'
        );

        $result = $data->stringify();

        $this->assertSame('0123456709498765432112345', $result);
    }

    /** @test */
    public function stringifyWithNineDigitMailerId(): void
    {
        $data = new IMBData(
            barcodeId: '01',
            serviceType: '234',
            mailerId: '123456789',
            serialNum: '012345',
            zip: '12345',
            plus4: '6789',
            deliveryPt: '01'
        );

        $result = $data->stringify();

        // 01 + 234 + 123456789 + 012345 + 12345 + 6789 + 01
        $this->assertSame('0123412345678901234512345678901', $result);
    }
}
