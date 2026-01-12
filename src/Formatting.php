<?php

declare(strict_types=1);

namespace Imb;

/**
 * Formatting utilities for USPS Intelligent Mail Barcode encoding/decoding.
 *
 * Contains the codeword lookup tables and mathematical operations
 * needed for barcode conversion.
 */
final class Formatting
{
    /**
     * @var array<int, int> Table mapping codeword indices to 13-bit codes
     */
    private static $encodeTable = [];

    /**
     * @var array<int, int> Table mapping 13-bit codes to codeword indices
     */
    private static $decodeTable = [];

    /**
     * @var array<int, int> Frame check sequence table
     */
    private static $fcsTable = [];

    /**
     * @var bool Whether tables have been initialized
     */
    private static $initialized = false;

    /**
     * Initialize the codeword tables if not already done.
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$encodeTable = array_fill(0, 1365, 0);
        self::$decodeTable = array_fill(0, 8192, 0);
        self::$fcsTable = array_fill(0, 8192, 0);

        self::buildCodeWords(5, 0, 1286);
        self::buildCodeWords(2, 1287, 1364);

        self::$initialized = true;
    }

    /**
     * Build tables of 13-bit codewords.
     *
     * @param int $bits Number of 1-bits in codeword
     * @param int $low Starting index for table
     * @param int $hi Ending index for table
     */
    private static function buildCodeWords(int $bits, int $low, int $hi): void
    {
        for ($fwd = 0; $fwd < 8192; $fwd++) {
            $pop = 0;
            $rev = 0;
            $tmp = $fwd;

            for ($bit = 0; $bit < 13; $bit++) {
                $pop += $tmp & 1;
                $rev = ($rev << 1) | ($tmp & 1);
                $tmp >>= 1;
            }

            if ($pop !== $bits) {
                continue;
            }

            if ($fwd === $rev) {
                // Palindromic codes go at the end of the table
                self::$encodeTable[$hi] = $fwd;
                self::$decodeTable[$fwd] = $hi;
                self::$decodeTable[$fwd ^ 8191] = $hi;
                self::$fcsTable[$fwd] = 0;
                self::$fcsTable[$fwd ^ 8191] = 1;
                $hi--;
            } elseif ($fwd < $rev) {
                // Add forward code to front of table
                self::$encodeTable[$low] = $fwd;
                self::$decodeTable[$fwd] = $low;
                self::$decodeTable[$fwd ^ 8191] = $low;
                self::$fcsTable[$fwd] = 0;
                self::$fcsTable[$fwd ^ 8191] = 1;
                $low++;

                // Add reversed code to front of table
                self::$encodeTable[$low] = $rev;
                self::$decodeTable[$rev] = $low;
                self::$decodeTable[$rev ^ 8191] = $low;
                self::$fcsTable[$rev] = 0;
                self::$fcsTable[$rev ^ 8191] = 1;
                $low++;
            }
        }
    }

    /**
     * Get the encode table.
     *
     * @return array<int, int>
     */
    public static function getEncodeTable(): array
    {
        self::initialize();
        return self::$encodeTable;
    }

    /**
     * Get the decode table.
     *
     * @return array<int, int>
     */
    public static function getDecodeTable(): array
    {
        self::initialize();
        return self::$decodeTable;
    }

    /**
     * Get the FCS table.
     *
     * @return array<int, int>
     */
    public static function getFcsTable(): array
    {
        self::initialize();
        return self::$fcsTable;
    }

    /**
     * Get a value from the encode table.
     *
     * @param int $index
     * @return int
     */
    public static function getEncodeValue(int $index): int
    {
        self::initialize();
        return self::$encodeTable[$index] ?? 0;
    }

    /**
     * Get a value from the decode table.
     *
     * @param int $index
     * @return int|null Returns null if index is not valid
     */
    public static function getDecodeValue(int $index): ?int
    {
        self::initialize();
        return self::$decodeTable[$index] ?? null;
    }

    /**
     * Get a value from the FCS table.
     *
     * @param int $index
     * @return int
     */
    public static function getFcsValue(int $index): int
    {
        self::initialize();
        return self::$fcsTable[$index] ?? 0;
    }

    /**
     * Clean a string by converting to uppercase and removing whitespace.
     *
     * @param string|null $str
     * @return string
     */
    public static function cleanString(?string $str): string
    {
        if ($str === null) {
            return '';
        }
        return strtoupper(preg_replace('/\s/', '', $str) ?? '');
    }

    /**
     * Check if a multi-precision number array is zero.
     *
     * @param array<int, int> $num Array of 11-bit words
     * @return bool
     */
    public static function isZero(array $num): bool
    {
        for ($n = count($num) - 1; $n >= 0; $n--) {
            if ($num[$n] !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add a value to a multi-precision number array.
     *
     * @param array<int, int> &$num Array of 11-bit words (modified in place)
     * @param int $add Value to add
     */
    public static function add(array &$num, int $add): void
    {
        for ($n = count($num) - 1; $n >= 0 && $add !== 0; $n--) {
            $x = $num[$n] + $add;
            $add = $x >> 11;
            $num[$n] = $x & 0x7ff;
        }
    }

    /**
     * Multiply a multi-precision number and add a value.
     *
     * @param array<int, int> &$num Array of 11-bit words (modified in place)
     * @param int $multiplier Multiplier value
     * @param int $add Value to add after multiplication
     */
    public static function multiplyAndAdd(array &$num, int $multiplier, int $add): void
    {
        for ($n = count($num) - 1; $n >= 0; $n--) {
            $x = $num[$n] * $multiplier + $add;
            $add = $x >> 11;
            $num[$n] = $x & 0x7ff;
        }
    }

    /**
     * Divide a multi-precision number and return the remainder.
     *
     * @param array<int, int> &$num Array of 11-bit words (modified in place)
     * @param int $divisor Divisor value
     * @return int Remainder
     */
    public static function divideModulus(array &$num, int $divisor): int
    {
        $mod = 0;
        $len = count($num);

        for ($n = 0; $n < $len; $n++) {
            $x = $num[$n] + ($mod << 11);
            $q = (int) floor($x / $divisor);
            $num[$n] = $q;
            $mod = $x - $q * $divisor;
        }

        return $mod;
    }

    /**
     * Calculate 11-bit frame check sequence for an array of 11-bit words.
     *
     * @param array<int, int> $num Array of 11-bit words
     * @return int Frame check sequence
     */
    public static function calculateFrameCheck(array $num): int
    {
        $fcs = 0x1f0;
        $len = count($num);

        for ($n = 0; $n < $len; $n++) {
            $fcs ^= $num[$n];
            for ($bit = 0; $bit < 11; $bit++) {
                $fcs <<= 1;
                if ($fcs & 0x800) {
                    $fcs ^= 0xf35;
                }
            }
        }

        return $fcs;
    }

    /**
     * Convert computed character array to barcode string.
     *
     * @param array<int, int> $chars Array of character values
     * @return string 65-character barcode string (A, D, F, T)
     */
    public static function charactersToText(array $chars): string
    {
        $barcode = '';

        for ($n = 0; $n < 65; $n++) {
            $descChar = BarcodeToBit::DESC_CHAR[$n];
            $descBit = BarcodeToBit::DESC_BIT[$n];
            $ascChar = BarcodeToBit::ASC_CHAR[$n];
            $ascBit = BarcodeToBit::ASC_BIT[$n];

            if ($chars[$descChar] & $descBit) {
                if ($chars[$ascChar] & $ascBit) {
                    $barcode .= 'F';
                } else {
                    $barcode .= 'D';
                }
            } else {
                if ($chars[$ascChar] & $ascBit) {
                    $barcode .= 'A';
                } else {
                    $barcode .= 'T';
                }
            }
        }

        return $barcode;
    }

    /**
     * Convert barcode string to character array.
     *
     * @param string $barcode Barcode string
     * @param bool $strict If true, return null on invalid characters
     * @return array<int, int>|null Array of character values or null if invalid
     */
    public static function textToCharacters(string $barcode, bool $strict = false): ?array
    {
        $barcode = self::cleanString($barcode);
        $chars = array_fill(0, 10, 0);

        for ($n = 0; $n < 65; $n++) {
            $char = $barcode[$n] ?? '';
            $descChar = BarcodeToBit::DESC_CHAR[$n];
            $descBit = BarcodeToBit::DESC_BIT[$n];
            $ascChar = BarcodeToBit::ASC_CHAR[$n];
            $ascBit = BarcodeToBit::ASC_BIT[$n];

            switch ($char) {
                case 'T':
                case 'S':
                    // Track bar - no bits set
                    break;
                case 'D':
                    // Descending bar
                    $chars[$descChar] |= $descBit;
                    break;
                case 'A':
                    // Ascending bar
                    $chars[$ascChar] |= $ascBit;
                    break;
                case 'F':
                    // Full bar
                    $chars[$descChar] |= $descBit;
                    $chars[$ascChar] |= $ascBit;
                    break;
                default:
                    if ($strict) {
                        return null;
                    }
                    break;
            }
        }

        return $chars;
    }
}
