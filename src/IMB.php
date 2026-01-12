<?php

declare(strict_types=1);

namespace Imb;

use Imb\Exception\DecodingException;
use Imb\Exception\EncodingException;
use Imb\Exception\ValidationException;

/**
 * USPS Intelligent Mail Barcode (IMB) Library.
 *
 * A lightweight PHP library for encoding and decoding USPS Intelligent Mail Barcodes.
 * This is the main facade class providing a simple API for barcode operations.
 *
 * @example
 * ```php
 * // Encode
 * $barcode = IMB::encode([
 *     'barcode_id' => '01',
 *     'service_type' => '234',
 *     'mailer_id' => '567094',
 *     'serial_num' => '987654321',
 *     'zip' => '01234',
 *     'plus4' => '5678',
 *     'delivery_pt' => '90',
 * ]);
 *
 * // Decode
 * $data = IMB::decode('ATTFAATTFTADFDATDDADAATTTTTTTTADFFFFFDFAFATTDAADATDDDTADAFFDFDTFT');
 * ```
 */
final class IMB
{
    private static ?Encoder $encoder = null;
    private static ?Decoder $decoder = null;

    /**
     * Prevent instantiation of this static facade.
     */
    private function __construct()
    {
    }

    /**
     * Get the encoder instance.
     *
     * @return Encoder
     */
    public static function getEncoder(): Encoder
    {
        if (self::$encoder === null) {
            self::$encoder = new Encoder();
        }
        return self::$encoder;
    }

    /**
     * Get the decoder instance.
     *
     * @return Decoder
     */
    public static function getDecoder(): Decoder
    {
        if (self::$decoder === null) {
            self::$decoder = new Decoder();
        }
        return self::$decoder;
    }

    /**
     * Encode IMB data into a barcode string.
     *
     * @param IMBData|array<string, string|null> $data IMB data to encode
     * @return string 65-character barcode string (A, D, F, T)
     * @throws ValidationException If input validation fails
     * @throws EncodingException If encoding fails
     *
     * @example
     * ```php
     * $barcode = IMB::encode([
     *     'barcode_id' => '01',
     *     'service_type' => '234',
     *     'mailer_id' => '567094',
     *     'serial_num' => '987654321',
     * ]);
     * // Returns: "ATTFAATTFTADFDATDDADAATTTTTTTTADFFFFFDFAFATTDAADATDDDTADAFFDFDTFT"
     * ```
     */
    public static function encode(IMBData|array $data): string
    {
        return self::getEncoder()->encode($data);
    }

    /**
     * Decode a barcode string into IMB data.
     *
     * @param string $barcode 65-character barcode string
     * @return DecodeResult Decoded data with optional repair info
     * @throws DecodingException If barcode cannot be decoded
     *
     * @example
     * ```php
     * $result = IMB::decode('ATTFAATTFTADFDATDDADAATTTTTTTTADFFFFFDFAFATTDAADATDDDTADAFFDFDTFT');
     * echo $result->data->barcodeId; // "01"
     * echo $result->data->serviceType; // "234"
     * ```
     */
    public static function decode(string $barcode): DecodeResult
    {
        return self::getDecoder()->decode($barcode);
    }

    /**
     * Decode a barcode and return as array.
     *
     * @param string $barcode 65-character barcode string
     * @return array<string, mixed> Decoded data as associative array
     * @throws DecodingException If barcode cannot be decoded
     */
    public static function decodeToArray(string $barcode): array
    {
        return self::decode($barcode)->toArray();
    }

    /**
     * Validate IMB data without encoding.
     *
     * @param IMBData|array<string, string|null> $data IMB data to validate
     * @return bool True if valid
     */
    public static function validate(IMBData|array $data): bool
    {
        try {
            self::encode($data);
            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * Validate a barcode string without fully decoding.
     *
     * @param string $barcode Barcode string to validate
     * @return bool True if valid
     */
    public static function validateBarcode(string $barcode): bool
    {
        try {
            self::decode($barcode);
            return true;
        } catch (DecodingException) {
            return false;
        }
    }

    /**
     * Create an IMBData object from an array.
     *
     * @param array<string, string|null> $data
     * @return IMBData
     */
    public static function createData(array $data): IMBData
    {
        return IMBData::fromArray($data);
    }
}
