# IMB - USPS Intelligent Mail Barcode Library for PHP

[![PHP Version](https://img.shields.io/badge/php-%5E7.4%20%7C%7C%20%5E8.0-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-CC0--1.0-green)](LICENSE)

A lightweight PHP library for encoding and decoding USPS Intelligent Mail Barcodes (IMB).

> **Note:** This is a PHP port/wrapper of the original Node.js library [BossRoxall/imb](https://github.com/BossRoxall/imb) created by [BossRoxall](https://github.com/BossRoxall). All credit for the original algorithm implementation goes to the original author.

## Features

- **Encode** postal information into 65-character IMB barcode strings
- **Decode** IMB barcode strings back to postal data
- **Error correction** for damaged barcodes
- **Full validation** of input data
- **CLI tool** for command-line operations
- **PSR-4 autoloading** compatible
- **PHPStan Level 8** compatible
- **100% test coverage ready**

## Installation

### Via Composer

```bash
composer require erajkhatiwada/imb
```

### Manual Installation

1. Clone the repository
2. Run `composer install`

## Quick Start

### Encoding

```php
<?php

use Imb\IMB;

// Encode with required fields only
$barcode = IMB::encode([
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
]);
// Output: "ADFTATFTDTADTDAFF..." (65 characters)

// Encode with full routing information
$barcode = IMB::encode([
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
    'zip' => '12345',
    'plus4' => '6789',
    'delivery_pt' => '01',
]);
```

### Decoding

```php
<?php

use Imb\IMB;

$result = IMB::decode('ADFTATFTDTADTDAFF...');

echo $result->data->barcodeId;    // "01"
echo $result->data->serviceType;  // "234"
echo $result->data->mailerId;     // "567094"
echo $result->data->serialNum;    // "987654321"
echo $result->data->zip;          // "12345" (if present)
echo $result->data->plus4;        // "6789" (if present)
echo $result->data->deliveryPt;   // "01" (if present)

// Convert to array
$array = IMB::decodeToArray('ADFTATFTDTADTDAFF...');
```

### Validation

```php
<?php

use Imb\IMB;

// Validate input data
$isValid = IMB::validate([
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
]);

// Validate barcode string
$isValid = IMB::validateBarcode('ADFTATFTDTADTDAFF...');
```

## Input Fields

### Required Fields

| Field | Description | Format |
|-------|-------------|--------|
| `barcode_id` | Barcode identifier | 2 digits, second digit must be 0-4 |
| `service_type` | Service type code | 3 digits |
| `mailer_id` | USPS mailer ID | 6 or 9 digits |
| `serial_num` | Serial number | Varies (mailer_id + serial_num = 15 digits) |

### Optional Fields (Routing)

| Field | Description | Format |
|-------|-------------|--------|
| `zip` | ZIP code | 5 digits |
| `plus4` | ZIP+4 extension | 4 digits (requires zip) |
| `delivery_pt` | Delivery point | 2 digits |

## Output Format

The barcode is a 65-character string using only these characters:

- **A** - Ascending bar
- **D** - Descending bar
- **F** - Full bar (both ascending and descending)
- **T** - Track bar (neither ascending nor descending)

## CLI Usage

The library includes a command-line interface:

```bash
# Show help
./bin/imb help

# Encode
./bin/imb encode --barcode-id=01 --service-type=234 --mailer-id=567094 --serial-num=987654321

# Encode with routing
./bin/imb encode --barcode-id=01 --service-type=234 --mailer-id=567094 --serial-num=987654321 \
    --zip=12345 --plus4=6789 --delivery-pt=01

# Decode
./bin/imb decode ADFTATFTDTADTDAFFADADTDAFDTDTFATDTDATDFDATDFDAFDTAFDAFDAFDAFDAFDA
```

## Advanced Usage

### Using IMBData Objects

```php
<?php

use Imb\IMB;
use Imb\IMBData;

// Create data object directly
$data = new IMBData(
    barcodeId: '01',
    serviceType: '234',
    mailerId: '567094',
    serialNum: '987654321',
    zip: '12345'
);

$barcode = IMB::encode($data);

// Create from array
$data = IMBData::fromArray([
    'barcode_id' => '01',
    'service_type' => '234',
    'mailer_id' => '567094',
    'serial_num' => '987654321',
]);
```

### Using Encoder/Decoder Directly

```php
<?php

use Imb\Encoder;
use Imb\Decoder;

$encoder = new Encoder();
$decoder = new Decoder();

$barcode = $encoder->encode([...]);
$result = $decoder->decode($barcode);
```

### Error Handling

```php
<?php

use Imb\IMB;
use Imb\Exception\ValidationException;
use Imb\Exception\DecodingException;

try {
    $barcode = IMB::encode([...]);
} catch (ValidationException $e) {
    echo "Invalid input: " . $e->getMessage();
}

try {
    $result = IMB::decode('invalid-barcode');
} catch (DecodingException $e) {
    echo "Could not decode: " . $e->getMessage();
}
```

### Damaged Barcode Recovery

The decoder can attempt to recover damaged barcodes:

```php
<?php

use Imb\IMB;

$result = IMB::decode($damagedBarcode);

if ($result->wasDamaged()) {
    echo "Barcode was damaged and repaired";
    echo "Suggested correction: " . $result->suggest;
    // $result->highlight shows which positions were corrected
}
```

## API Reference

### IMB (Static Facade)

| Method | Description |
|--------|-------------|
| `encode(array\|IMBData $data): string` | Encode to barcode |
| `decode(string $barcode): DecodeResult` | Decode barcode |
| `decodeToArray(string $barcode): array` | Decode to array |
| `validate(array\|IMBData $data): bool` | Validate input data |
| `validateBarcode(string $barcode): bool` | Validate barcode string |
| `createData(array $data): IMBData` | Create IMBData object |
| `getEncoder(): Encoder` | Get encoder instance |
| `getDecoder(): Decoder` | Get decoder instance |

### IMBData

| Property | Type | Description |
|----------|------|-------------|
| `barcodeId` | string | 2-digit barcode ID |
| `serviceType` | string | 3-digit service type |
| `mailerId` | string | 6 or 9 digit mailer ID |
| `serialNum` | string | Serial number |
| `zip` | ?string | 5-digit ZIP |
| `plus4` | ?string | 4-digit ZIP+4 |
| `deliveryPt` | ?string | 2-digit delivery point |

### DecodeResult

| Property | Type | Description |
|----------|------|-------------|
| `data` | IMBData | Decoded data |
| `message` | ?string | Status message |
| `suggest` | ?string | Suggested correction |
| `highlight` | ?array | Positions that differ |

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run PHPStan
composer phpstan

# Run code style check
composer cs

# Run all checks
composer check
```

## Requirements

- PHP 7.4 or higher
- No external dependencies for runtime
- PHPUnit 9.6+ for testing (dev)

## License

This project is released under the [CC0 1.0 Universal (Public Domain)](LICENSE) license, same as the original Node.js library.

## Acknowledgments

This PHP library is a direct port of the excellent [Node.js IMB library](https://github.com/BossRoxall/imb) created by [BossRoxall](https://github.com/BossRoxall). The encoding/decoding algorithms, bit permutation tables, and overall architecture are derived from that original work.

Special thanks to:
- **[BossRoxall](https://github.com/BossRoxall)** - Author of the original [Node.js IMB library](https://github.com/BossRoxall/imb)
- **Bob Codes** - Original IMB algorithm implementation (released under CC0)
- **USPS** - Intelligent Mail Barcode specification

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Related Projects

- [BossRoxall/imb](https://github.com/BossRoxall/imb) - Original Node.js implementation (the source of this port)
