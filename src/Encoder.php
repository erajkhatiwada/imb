<?php

declare(strict_types=1);

namespace Imb;

use Imb\Exception\EncodingException;
use Imb\Exception\ValidationException;

/**
 * Encoder for USPS Intelligent Mail Barcodes.
 *
 * Transforms postal information into a 65-character barcode string
 * composed of letters A (Ascending), D (Descending), F (Full), and T (Track).
 */
final class Encoder
{
    /**
     * Encode IMB data into a barcode string.
     *
     * @param IMBData|array<string, string|null> $data IMB data to encode
     * @return string 65-character barcode string
     * @throws ValidationException If input validation fails
     * @throws EncodingException If encoding fails
     */
    public function encode(IMBData|array $data): string
    {
        if (is_array($data)) {
            $data = IMBData::fromArray($data);
        }

        $this->validate($data);

        return $this->encodeFields($data);
    }

    /**
     * Validate the input data.
     *
     * @param IMBData $data
     * @throws ValidationException
     */
    private function validate(IMBData $data): void
    {
        // Clean all string fields
        $zip = Formatting::cleanString($data->zip);
        $plus4 = Formatting::cleanString($data->plus4);
        $deliveryPt = Formatting::cleanString($data->deliveryPt);
        $barcodeId = Formatting::cleanString($data->barcodeId);
        $serviceType = Formatting::cleanString($data->serviceType);
        $mailerId = Formatting::cleanString($data->mailerId);
        $serialNum = Formatting::cleanString($data->serialNum);

        // ZIP code is optional, but must be 5 digits if provided
        if ($zip !== '' && !$this->checkDigits($zip, 5)) {
            throw new ValidationException('Zip code must be 5 digits');
        }

        // ZIP+4 requires ZIP and must be 4 digits
        if ($plus4 !== '') {
            if ($zip === '') {
                throw new ValidationException('Zip code is required if plus4 is provided');
            }
            if (!$this->checkDigits($plus4, 4)) {
                throw new ValidationException('plus4 must be 4 digits');
            }
        }

        // Delivery point must be 2 digits
        if ($deliveryPt !== '' && !$this->checkDigits($deliveryPt, 2)) {
            throw new ValidationException('Delivery Point must be 2 digits');
        }

        // Barcode ID must be 2 digits
        if (!$this->checkDigits($barcodeId, 2)) {
            throw new ValidationException('Barcode ID must be 2 digits');
        }

        // Second digit of barcode ID must be 0-4
        if ($barcodeId[1] >= '5') {
            throw new ValidationException('Second digit of Barcode ID must be 0-4');
        }

        // Service type must be 3 digits
        if (!$this->checkDigits($serviceType, 3)) {
            throw new ValidationException('Service Type must be 3 digits');
        }

        // Mailer ID must be 6 or 9 digits
        if (!$this->checkDigits($mailerId, 6, 9)) {
            throw new ValidationException('Mailer ID must be 6 or 9 digits');
        }

        // Mailer ID + Serial Number must be 15 digits total
        if (!$this->checkDigits($serialNum) || strlen($mailerId) + strlen($serialNum) !== 15) {
            throw new ValidationException('Mailer ID and Serial Number together must be 15 digits');
        }
    }

    /**
     * Check if a string contains only digits and optionally has a specific length.
     *
     * @param string $str String to check
     * @param int|null $length Required length (or null for any length)
     * @param int|null $altLength Alternative acceptable length
     * @return bool
     */
    private function checkDigits(string $str, ?int $length = null, ?int $altLength = null): bool
    {
        // Check for non-digits
        if (preg_match('/\D/', $str)) {
            return false;
        }

        // If no length requirement, just check for digits
        if ($length === null) {
            return true;
        }

        // Check length matches expected value(s)
        $strLen = strlen($str);
        return $strLen === $length || ($altLength !== null && $strLen === $altLength);
    }

    /**
     * Perform the actual encoding of validated fields.
     *
     * @param IMBData $data
     * @return string
     */
    private function encodeFields(IMBData $data): string
    {
        $zip = Formatting::cleanString($data->zip);
        $plus4 = Formatting::cleanString($data->plus4);
        $deliveryPt = Formatting::cleanString($data->deliveryPt);
        $barcodeId = Formatting::cleanString($data->barcodeId);
        $serviceType = Formatting::cleanString($data->serviceType);
        $mailerId = Formatting::cleanString($data->mailerId);
        $serialNum = Formatting::cleanString($data->serialNum);

        // Initialize multi-precision number array (10 x 11-bit words)
        $num = array_fill(0, 10, 0);
        $marker = 0;

        // Encode routing information
        if ($zip !== '') {
            $num[9] = (int) $zip;
            $marker += 1;
        }

        if ($plus4 !== '') {
            Formatting::multiplyAndAdd($num, 10000, (int) $plus4);
            $marker += 100000;
        }

        if ($deliveryPt !== '') {
            Formatting::multiplyAndAdd($num, 100, (int) $deliveryPt);
            $marker += 1000000000;
        }

        Formatting::add($num, $marker);

        // Encode tracking information
        Formatting::multiplyAndAdd($num, 10, (int) $barcodeId[0]);
        Formatting::multiplyAndAdd($num, 5, (int) $barcodeId[1]);
        Formatting::multiplyAndAdd($num, 1000, (int) $serviceType);

        if (strlen($mailerId) === 6) {
            Formatting::multiplyAndAdd($num, 1000000, (int) $mailerId);
            Formatting::multiplyAndAdd($num, 100000, 0); // Multiply in two steps to avoid overflow
            Formatting::multiplyAndAdd($num, 10000, (int) $serialNum);
        } else {
            Formatting::multiplyAndAdd($num, 10000, 0);
            Formatting::multiplyAndAdd($num, 100000, (int) $mailerId);
            Formatting::multiplyAndAdd($num, 1000000, (int) $serialNum);
        }

        // Calculate frame check sequence
        $fcs = Formatting::calculateFrameCheck($num);

        // Convert to codewords
        $cw = array_fill(0, 10, 0);
        $cw[9] = Formatting::divideModulus($num, 636) << 1;

        for ($n = 8; $n > 0; $n--) {
            $cw[$n] = Formatting::divideModulus($num, 1365);
        }

        $cw[0] = ($num[8] << 11) | $num[9];
        if ($fcs & (1 << 10)) {
            $cw[0] += 659;
        }

        // Convert codewords to characters
        $chars = array_fill(0, 10, 0);
        for ($n = 0; $n < 10; $n++) {
            $chars[$n] = Formatting::getEncodeValue($cw[$n]);
            if ($fcs & (1 << $n)) {
                $chars[$n] ^= 8191;
            }
        }

        return Formatting::charactersToText($chars);
    }
}
