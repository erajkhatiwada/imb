<?php

declare(strict_types=1);

namespace Imb\Tests\Integration;

use Imb\IMB;


use PHPUnit\Framework\TestCase;

/**
 * Integration tests that verify the complete encode/decode cycle works correctly
 * for a wide variety of input data combinations.
 */
final class EncodeDecodeIntegrationTest extends TestCase
{
    /**
     * @test
     * @dataProvider allFieldCombinationsProvider
     */
    public function encodeDecodeRoundTrip(array $input): void
    {
        $barcode = IMB::encode($input);
        $decoded = IMB::decode($barcode);

        $this->assertSame($input['barcode_id'], $decoded->data->barcodeId);
        $this->assertSame($input['service_type'], $decoded->data->serviceType);
        $this->assertSame($input['mailer_id'], $decoded->data->mailerId);
        $this->assertSame($input['serial_num'], $decoded->data->serialNum);

        if (isset($input['zip'])) {
            $this->assertSame($input['zip'], $decoded->data->zip);
        }
        if (isset($input['plus4'])) {
            $this->assertSame($input['plus4'], $decoded->data->plus4);
        }
        if (isset($input['delivery_pt'])) {
            $this->assertSame($input['delivery_pt'], $decoded->data->deliveryPt);
        }
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function allFieldCombinationsProvider(): array
    {
        return [
            // Basic tracking only (no routing)
            'basic_6digit_mailer' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
            ]],
            'basic_9digit_mailer' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '901234567',  // 9-digit mailer IDs must start with 9
                'serial_num' => '012345',
            ]],

            // With ZIP only
            'with_zip_6digit' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
                'zip' => '12345',
            ]],
            'with_zip_9digit' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '901234567',  // 9-digit mailer IDs must start with 9
                'serial_num' => '012345',
                'zip' => '54321',
            ]],

            // With ZIP+4
            'with_zip_plus4_6digit' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
                'zip' => '12345',
                'plus4' => '6789',
            ]],
            'with_zip_plus4_9digit' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '901234567',  // 9-digit mailer IDs must start with 9
                'serial_num' => '012345',
                'zip' => '54321',
                'plus4' => '9876',
            ]],

            // Full routing (ZIP+4+DP)
            'full_routing_6digit' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '567094',
                'serial_num' => '987654321',
                'zip' => '12345',
                'plus4' => '6789',
                'delivery_pt' => '01',
            ]],
            'full_routing_9digit' => [[
                'barcode_id' => '01',
                'service_type' => '234',
                'mailer_id' => '901234567',  // 9-digit mailer IDs must start with 9
                'serial_num' => '012345',
                'zip' => '54321',
                'plus4' => '9876',
                'delivery_pt' => '99',
            ]],

            // Different barcode IDs
            'barcode_id_00' => [[
                'barcode_id' => '00',
                'service_type' => '100',
                'mailer_id' => '111111',
                'serial_num' => '111111111',
            ]],
            'barcode_id_01' => [[
                'barcode_id' => '01',
                'service_type' => '200',
                'mailer_id' => '222222',
                'serial_num' => '222222222',
            ]],
            'barcode_id_02' => [[
                'barcode_id' => '02',
                'service_type' => '300',
                'mailer_id' => '333333',
                'serial_num' => '333333333',
            ]],
            'barcode_id_03' => [[
                'barcode_id' => '03',
                'service_type' => '400',
                'mailer_id' => '444444',
                'serial_num' => '444444444',
            ]],
            'barcode_id_04' => [[
                'barcode_id' => '04',
                'service_type' => '500',
                'mailer_id' => '555555',
                'serial_num' => '555555555',
            ]],
            'barcode_id_90' => [[
                'barcode_id' => '90',
                'service_type' => '600',
                'mailer_id' => '666666',
                'serial_num' => '666666666',
            ]],
            'barcode_id_94' => [[
                'barcode_id' => '94',
                'service_type' => '700',
                'mailer_id' => '777777',
                'serial_num' => '777777777',
            ]],

            // Edge cases
            'all_zeros' => [[
                'barcode_id' => '00',
                'service_type' => '000',
                'mailer_id' => '000000',
                'serial_num' => '000000000',
            ]],
            'all_nines_6digit' => [[
                'barcode_id' => '94',
                'service_type' => '999',
                'mailer_id' => '888888',  // Cannot use 999999 - mailer IDs starting with 9 are 9-digit
                'serial_num' => '999999999',
                'zip' => '99999',
                'plus4' => '9999',
                'delivery_pt' => '99',
            ]],
            'mixed_digits' => [[
                'barcode_id' => '13',
                'service_type' => '456',
                'mailer_id' => '123456',
                'serial_num' => '789012345',
                'zip' => '67890',
                'plus4' => '1234',
                'delivery_pt' => '56',
            ]],
        ];
    }

    /** @test */
    public function multipleSequentialEncodeDecodes(): void
    {
        // Test that the library maintains state correctly across multiple operations
        $testCases = [
            ['barcode_id' => '01', 'service_type' => '100', 'mailer_id' => '111111', 'serial_num' => '111111111'],
            ['barcode_id' => '02', 'service_type' => '200', 'mailer_id' => '222222', 'serial_num' => '222222222'],
            ['barcode_id' => '03', 'service_type' => '300', 'mailer_id' => '333333', 'serial_num' => '333333333'],
        ];

        $barcodes = [];

        // Encode all
        foreach ($testCases as $index => $data) {
            $barcodes[$index] = IMB::encode($data);
        }

        // Decode all and verify
        foreach ($testCases as $index => $expected) {
            $decoded = IMB::decode($barcodes[$index]);
            $this->assertSame($expected['barcode_id'], $decoded->data->barcodeId);
            $this->assertSame($expected['service_type'], $decoded->data->serviceType);
            $this->assertSame($expected['mailer_id'], $decoded->data->mailerId);
            $this->assertSame($expected['serial_num'], $decoded->data->serialNum);
        }
    }

    /** @test */
    public function barcodeUniqueness(): void
    {
        // Verify that different inputs produce different barcodes
        $data1 = ['barcode_id' => '01', 'service_type' => '234', 'mailer_id' => '567094', 'serial_num' => '987654321'];
        $data2 = ['barcode_id' => '01', 'service_type' => '234', 'mailer_id' => '567094', 'serial_num' => '987654322'];
        $data3 = ['barcode_id' => '01', 'service_type' => '235', 'mailer_id' => '567094', 'serial_num' => '987654321'];

        $barcode1 = IMB::encode($data1);
        $barcode2 = IMB::encode($data2);
        $barcode3 = IMB::encode($data3);

        $this->assertNotSame($barcode1, $barcode2);
        $this->assertNotSame($barcode1, $barcode3);
        $this->assertNotSame($barcode2, $barcode3);
    }

    /** @test */
    public function encodeDeterminism(): void
    {
        // Same input should always produce same output
        $data = [
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
            'zip' => '12345',
        ];

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = IMB::encode($data);
        }

        $this->assertCount(1, array_unique($results));
    }

    /** @test */
    public function largeBatchProcessing(): void
    {
        // Test processing many barcodes
        $count = 100;
        $barcodes = [];
        $originalData = [];

        // Generate and encode
        for ($i = 0; $i < $count; $i++) {
            $data = [
                'barcode_id' => '0' . ($i % 5),
                'service_type' => str_pad((string)($i % 1000), 3, '0', STR_PAD_LEFT),
                'mailer_id' => str_pad((string)($i * 7 % 1000000), 6, '0', STR_PAD_LEFT),
                'serial_num' => str_pad((string)($i * 13 % 1000000000), 9, '0', STR_PAD_LEFT),
            ];
            $originalData[$i] = $data;
            $barcodes[$i] = IMB::encode($data);
        }

        // Decode and verify all
        for ($i = 0; $i < $count; $i++) {
            $decoded = IMB::decode($barcodes[$i]);
            $this->assertSame($originalData[$i]['barcode_id'], $decoded->data->barcodeId);
            $this->assertSame($originalData[$i]['service_type'], $decoded->data->serviceType);
            $this->assertSame($originalData[$i]['mailer_id'], $decoded->data->mailerId);
            $this->assertSame($originalData[$i]['serial_num'], $decoded->data->serialNum);
        }
    }

    /** @test */
    public function barcodeFormat(): void
    {
        $barcode = IMB::encode([
            'barcode_id' => '01',
            'service_type' => '234',
            'mailer_id' => '567094',
            'serial_num' => '987654321',
        ]);

        // Verify barcode format
        $this->assertSame(65, strlen($barcode));
        $this->assertMatchesRegularExpression('/^[ADFT]+$/', $barcode);

        // Verify all four character types can appear
        // (depending on the data, some barcodes may not have all types,
        // but the format should only allow these)
        foreach (str_split($barcode) as $char) {
            $this->assertContains($char, ['A', 'D', 'F', 'T']);
        }
    }

    /** @test */
    public function decodeOutputFormat(): void
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

        $barcode = IMB::encode($original);
        $result = IMB::decode($barcode);

        // Check structure
        $this->assertObjectHasProperty('data', $result);
        $this->assertObjectHasProperty('message', $result);
        $this->assertObjectHasProperty('suggest', $result);
        $this->assertObjectHasProperty('highlight', $result);

        // For valid barcode, message/suggest/highlight should be null
        $this->assertNull($result->message);
        $this->assertNull($result->suggest);
        $this->assertNull($result->highlight);
    }
}
