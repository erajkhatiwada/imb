<?php

/**
 * Basic Usage Examples for IMB Library
 *
 * This file demonstrates the most common use cases for encoding and
 * decoding USPS Intelligent Mail Barcodes.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Imb\IMB;
use Imb\IMBData;
use Imb\Exception\ValidationException;
use Imb\Exception\DecodingException;

echo "=== IMB Library Basic Usage Examples ===\n\n";

// ============================================================================
// Example 1: Basic Encoding (Required Fields Only)
// ============================================================================
echo "1. Basic Encoding (Required Fields Only)\n";
echo str_repeat('-', 50) . "\n";

$data = [
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
];

$barcode = IMB::encode($data);
echo "Input:\n";
echo "  Barcode ID: {$data['barcode_id']}\n";
echo "  Service Type: {$data['service_type']}\n";
echo "  Mailer ID: {$data['mailer_id']}\n";
echo "  Serial Number: {$data['serial_num']}\n";
echo "\nEncoded Barcode:\n  $barcode\n\n";

// ============================================================================
// Example 2: Encoding with ZIP Code
// ============================================================================
echo "2. Encoding with ZIP Code\n";
echo str_repeat('-', 50) . "\n";

$dataWithZip = [
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
    'zip' => '12345',
];

$barcodeWithZip = IMB::encode($dataWithZip);
echo "Input:\n";
echo "  ZIP: {$dataWithZip['zip']}\n";
echo "\nEncoded Barcode:\n  $barcodeWithZip\n\n";

// ============================================================================
// Example 3: Encoding with Full Routing (ZIP+4+Delivery Point)
// ============================================================================
echo "3. Encoding with Full Routing\n";
echo str_repeat('-', 50) . "\n";

$fullData = [
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
    'zip' => '12345',
    'plus4' => '6789',
    'delivery_pt' => '01',
];

$fullBarcode = IMB::encode($fullData);
echo "Input:\n";
echo "  ZIP: {$fullData['zip']}\n";
echo "  Plus4: {$fullData['plus4']}\n";
echo "  Delivery Point: {$fullData['delivery_pt']}\n";
echo "\nEncoded Barcode:\n  $fullBarcode\n\n";

// ============================================================================
// Example 4: Decoding a Barcode
// ============================================================================
echo "4. Decoding a Barcode\n";
echo str_repeat('-', 50) . "\n";

$result = IMB::decode($fullBarcode);
echo "Decoded Data:\n";
echo "  Barcode ID: {$result->data->barcodeId}\n";
echo "  Service Type: {$result->data->serviceType}\n";
echo "  Mailer ID: {$result->data->mailerId}\n";
echo "  Serial Number: {$result->data->serialNum}\n";
echo "  ZIP: {$result->data->zip}\n";
echo "  Plus4: {$result->data->plus4}\n";
echo "  Delivery Point: {$result->data->deliveryPt}\n\n";

// ============================================================================
// Example 5: Decoding to Array
// ============================================================================
echo "5. Decoding to Array\n";
echo str_repeat('-', 50) . "\n";

$array = IMB::decodeToArray($fullBarcode);
echo "Decoded Array:\n";
print_r($array);
echo "\n";

// ============================================================================
// Example 6: Using 9-Digit Mailer ID
// ============================================================================
echo "6. Using 9-Digit Mailer ID\n";
echo str_repeat('-', 50) . "\n";

$nineDigitData = [
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '123456789',  // 9 digits
    'serial_num' => '012345',     // 6 digits (total must be 15)
];

$nineDigitBarcode = IMB::encode($nineDigitData);
$decoded = IMB::decode($nineDigitBarcode);
echo "9-digit Mailer ID: {$nineDigitData['mailer_id']}\n";
echo "6-digit Serial: {$nineDigitData['serial_num']}\n";
echo "Barcode: $nineDigitBarcode\n";
echo "Decoded Mailer ID: {$decoded->data->mailerId}\n";
echo "Decoded Serial: {$decoded->data->serialNum}\n\n";

// ============================================================================
// Example 7: Validation
// ============================================================================
echo "7. Validation\n";
echo str_repeat('-', 50) . "\n";

$validData = [
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
];

$invalidData = [
    'barcode_id' => '99', // Invalid: second digit must be 0-4
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
];

echo "Valid data check: " . (IMB::validate($validData) ? 'VALID' : 'INVALID') . "\n";
echo "Invalid data check: " . (IMB::validate($invalidData) ? 'VALID' : 'INVALID') . "\n";
echo "Barcode validation: " . (IMB::validateBarcode($fullBarcode) ? 'VALID' : 'INVALID') . "\n\n";

// ============================================================================
// Example 8: Error Handling
// ============================================================================
echo "8. Error Handling\n";
echo str_repeat('-', 50) . "\n";

try {
    IMB::encode([
        'barcode_id' => '99', // Invalid
        'service_type' => '234',
        'mailer_id' => '567094',
        'serial_num' => '987654321',
    ]);
} catch (ValidationException $e) {
    echo "Caught ValidationException: {$e->getMessage()}\n";
}

try {
    IMB::decode('INVALID');
} catch (DecodingException $e) {
    echo "Caught DecodingException: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 9: Using IMBData Object
// ============================================================================
echo "9. Using IMBData Object\n";
echo str_repeat('-', 50) . "\n";

$imbData = new IMBData(
    barcodeId: '01',
    serviceType: '234',
    mailerId: '567094',
    serialNum: '987654321',
    zip: '12345'
);

$barcodeFromObject = IMB::encode($imbData);
echo "Created IMBData object\n";
echo "Barcode: $barcodeFromObject\n";
echo "Mailer ID Length: {$imbData->getMailerIdLength()} digits\n";
echo "Has 9-digit Mailer ID: " . ($imbData->hasNineDigitMailerId() ? 'Yes' : 'No') . "\n\n";

// ============================================================================
// Example 10: Round-Trip Verification
// ============================================================================
echo "10. Round-Trip Verification\n";
echo str_repeat('-', 50) . "\n";

$originalData = [
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
    'zip' => '12345',
    'plus4' => '6789',
    'delivery_pt' => '01',
];

$encoded = IMB::encode($originalData);
$decodedResult = IMB::decode($encoded);

echo "Original -> Encode -> Decode -> Compare\n";
echo "Barcode ID: {$originalData['barcode_id']} -> {$decodedResult->data->barcodeId} ";
echo ($originalData['barcode_id'] === $decodedResult->data->barcodeId ? '✓' : '✗') . "\n";
echo "Service Type: {$originalData['service_type']} -> {$decodedResult->data->serviceType} ";
echo ($originalData['service_type'] === $decodedResult->data->serviceType ? '✓' : '✗') . "\n";
echo "Mailer ID: {$originalData['mailer_id']} -> {$decodedResult->data->mailerId} ";
echo ($originalData['mailer_id'] === $decodedResult->data->mailerId ? '✓' : '✗') . "\n";
echo "Serial Num: {$originalData['serial_num']} -> {$decodedResult->data->serialNum} ";
echo ($originalData['serial_num'] === $decodedResult->data->serialNum ? '✓' : '✗') . "\n";
echo "ZIP: {$originalData['zip']} -> {$decodedResult->data->zip} ";
echo ($originalData['zip'] === $decodedResult->data->zip ? '✓' : '✗') . "\n";
echo "Plus4: {$originalData['plus4']} -> {$decodedResult->data->plus4} ";
echo ($originalData['plus4'] === $decodedResult->data->plus4 ? '✓' : '✗') . "\n";
echo "Delivery Pt: {$originalData['delivery_pt']} -> {$decodedResult->data->deliveryPt} ";
echo ($originalData['delivery_pt'] === $decodedResult->data->deliveryPt ? '✓' : '✗') . "\n";

echo "\n=== Examples Complete ===\n";
