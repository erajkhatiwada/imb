<?php

declare(strict_types=1);

namespace Imb;

use Imb\Exception\DecodingException;

/**
 * Decoder for USPS Intelligent Mail Barcodes.
 *
 * Parses a 65-character barcode string back into its component values.
 * Includes error correction capabilities for damaged barcodes.
 */
final class Decoder
{
    /**
     * Decode a barcode string into IMB data.
     *
     * @param string $barcode 65-character barcode string
     * @return DecodeResult Decoded data with optional repair info
     * @throws DecodingException If barcode cannot be decoded
     */
    public function decode(string $barcode): DecodeResult
    {
        $barcode = Formatting::cleanString($barcode);

        // Try strict decode first
        if (strlen($barcode) === 65) {
            $chars = Formatting::textToCharacters($barcode, true);

            if ($chars !== null) {
                $decoded = $this->decodeCharacters($chars);
                if ($decoded !== null) {
                    return new DecodeResult($decoded);
                }
            }
        }

        // Try to repair the barcode
        $barcode = $this->repairBarcode($barcode);

        if (strlen($barcode) !== 65) {
            throw new DecodingException('Barcode must be 65 characters long');
        }

        $chars = Formatting::textToCharacters($barcode, false);
        if ($chars === null) {
            throw new DecodingException('Invalid barcode');
        }

        $result = $this->repairCharacters($chars);

        if ($result !== null) {
            if ($result['suggest'] ?? null) {
                $result['highlight'] = $this->findDiffs($barcode, $result['suggest']);
            }

            return new DecodeResult(
                $result['data'],
                $result['message'] ?? null,
                $result['suggest'] ?? null,
                $result['highlight'] ?? null
            );
        }

        // Try flipping the barcode (upside down)
        $flippedBarcode = $this->flipBarcode($barcode);
        $chars = Formatting::textToCharacters($flippedBarcode, false);

        if ($chars !== null) {
            $result = $this->repairCharacters($chars);
            if ($result !== null && isset($result['data'])) {
                throw new DecodingException('Barcode seems to be upside down');
            }
        }

        throw new DecodingException('Invalid barcode');
    }

    /**
     * Decode character array to IMB data.
     *
     * @param array<int, int> $chars
     * @return IMBData|null
     */
    private function decodeCharacters(array $chars): ?IMBData
    {
        $fcs = 0;
        $cw = array_fill(0, 10, 0);
        $decodeTable = Formatting::getDecodeTable();
        $fcsTable = Formatting::getFcsTable();

        for ($n = 0; $n < 10; $n++) {
            $charValue = $chars[$n];
            if (!isset($decodeTable[$charValue])) {
                return null;
            }
            $cw[$n] = $decodeTable[$charValue];
            $fcs |= $fcsTable[$charValue] << $n;
        }

        // Validate codewords
        if ($cw[0] > 1317 || $cw[9] > 1270) {
            return null;
        }

        // If barcode is upside down, cw[9] will always be odd
        if ($cw[9] & 1) {
            return null;
        }

        $cw[9] >>= 1;
        if ($cw[0] > 658) {
            $cw[0] -= 659;
            $fcs |= 1 << 10;
        }

        // Convert codewords to binary
        $num = array_fill(0, 9, 0);
        $num[] = $cw[0];

        for ($n = 1; $n < 9; $n++) {
            Formatting::multiplyAndAdd($num, 1365, $cw[$n]);
        }

        Formatting::multiplyAndAdd($num, 636, $cw[9]);

        if (Formatting::calculateFrameCheck($num) !== $fcs) {
            return null;
        }

        // Decode tracking information
        $track = array_fill(0, 20, 0);
        for ($n = 19; $n >= 2; $n--) {
            $track[$n] = Formatting::divideModulus($num, 10);
        }

        $track[1] = Formatting::divideModulus($num, 5);
        $track[0] = Formatting::divideModulus($num, 10);

        // Decode routing information (zip code, etc)
        $pos = 11;
        $route = array_fill(0, 11, 0);

        for ($sz = 5; $sz >= 2; $sz--) {
            if ($sz === 3) {
                continue;
            }

            if (Formatting::isZero($num)) {
                break;
            }

            Formatting::add($num, -1);
            for ($n = 0; $n < $sz; $n++) {
                $route[--$pos] = Formatting::divideModulus($num, 10);
            }
        }

        if ($sz < 2 && !Formatting::isZero($num)) {
            return null;
        }

        // Build result
        $barcodeId = implode('', array_slice($track, 0, 2));
        $serviceType = implode('', array_slice($track, 2, 3));

        if ($track[5] === 9) {
            $mailerId = implode('', array_slice($track, 5, 9));
            $serialNum = implode('', array_slice($track, 14, 6));
        } else {
            $mailerId = implode('', array_slice($track, 5, 6));
            $serialNum = implode('', array_slice($track, 11, 9));
        }

        $zip = null;
        $plus4 = null;
        $deliveryPt = null;

        if ($pos <= 6) {
            $zip = implode('', array_slice($route, $pos, 5));
        }
        if ($pos <= 2) {
            $plus4 = implode('', array_slice($route, $pos + 5, 4));
        }
        if ($pos === 0) {
            $deliveryPt = implode('', array_slice($route, 9, 2));
        }

        return new IMBData(
            $barcodeId,
            $serviceType,
            $mailerId,
            $serialNum,
            $zip,
            $plus4,
            $deliveryPt
        );
    }

    /**
     * Try to repair damaged characters by finding valid alternatives.
     *
     * @param array<int, int> $chars
     * @return array{data: IMBData, message?: string, suggest?: string}|null
     */
    private function repairCharacters(array $chars): ?array
    {
        $decodeTable = Formatting::getDecodeTable();
        $prod = 1;
        $possible = array_fill(0, 10, []);

        for ($n = 0; $n < 10; $n++) {
            $c = $chars[$n];

            if (!isset($decodeTable[$c])) {
                // Try single bit flips to find valid characters
                for ($bit = 0; $bit < 13; $bit++) {
                    $d = $c ^ (1 << $bit);
                    if (isset($decodeTable[$d])) {
                        $possible[$n][] = $d;
                    }
                }
            } else {
                $possible[$n][] = $c;
            }

            // Don't let combinations get too high
            $prod *= count($possible[$n]);
            if ($prod === 0 || $prod > 1000) {
                return null;
            }
        }

        $newChars = array_fill(0, 10, 0);
        return $this->tryRepair($possible, $newChars, 0);
    }

    /**
     * Recursively try repair combinations.
     *
     * @param array<int, array<int, int>> $possible
     * @param array<int, int> $chars
     * @param int $pos
     * @return array{data: IMBData, message?: string, suggest?: string}|null
     */
    private function tryRepair(array $possible, array $chars, int $pos): ?array
    {
        $result = null;

        foreach ($possible[$pos] as $value) {
            $chars[$pos] = $value;

            if ($pos < 9) {
                $newResult = $this->tryRepair($possible, $chars, $pos + 1);
            } else {
                $decoded = $this->decodeCharacters($chars);
                if ($decoded !== null) {
                    $newResult = [
                        'data' => $decoded,
                        'suggest' => Formatting::charactersToText($chars),
                        'message' => 'Damaged barcode',
                    ];
                } else {
                    $newResult = null;
                }
            }

            if ($newResult !== null) {
                // Abort if multiple solutions found
                if ($result !== null) {
                    return ['data' => new IMBData('', '', '', ''), 'message' => 'Invalid barcode'];
                }
                $result = $newResult;
            }
        }

        return $result;
    }

    /**
     * Flip barcode characters (for upside-down detection).
     *
     * @param string $barcode
     * @return string
     */
    private function flipBarcode(string $barcode): string
    {
        $result = '';
        $len = strlen($barcode);

        for ($i = 0; $i < $len; $i++) {
            $char = $barcode[$i];
            switch ($char) {
                case 'A':
                    $result .= 'D';
                    break;
                case 'D':
                    $result .= 'A';
                    break;
                default:
                    $result .= $char;
                    break;
            }
        }

        return $result;
    }

    /**
     * Try to repair a barcode with wrong length.
     *
     * @param string $barcode
     * @return string
     */
    private function repairBarcode(string $barcode): string
    {
        $len = strlen($barcode);

        if ($len === 64) {
            $longer = true;
        } elseif ($len === 66) {
            $longer = false;
        } else {
            return $barcode;
        }

        $best = $barcode;
        $bestErrs = 5; // Don't try to repair if we can't get more than 5 right
        $decodeTable = Formatting::getDecodeTable();

        for ($pos = 0; $pos < 66; $pos++) {
            if ($longer) {
                $testCode = substr($barcode, 0, $pos) . 'X' . substr($barcode, $pos);
            } else {
                $testCode = substr($barcode, 0, $pos) . substr($barcode, $pos + 1);
            }

            $chars = Formatting::textToCharacters($testCode, false);
            if ($chars === null) {
                continue;
            }

            $errs = 0;
            for ($n = 0; $n < 10; $n++) {
                if (!isset($decodeTable[$chars[$n]])) {
                    $errs++;
                }
            }

            if ($errs < $bestErrs) {
                $bestErrs = $errs;
                $best = $testCode;
            }
        }

        return $best;
    }

    /**
     * Find differences between two strings.
     *
     * @param string $str1
     * @param string $str2
     * @return array<int, bool>
     */
    private function findDiffs(string $str1, string $str2): array
    {
        $len = min(strlen($str1), strlen($str2));
        $diffs = [];

        for ($n = 0; $n < $len; $n++) {
            $diffs[$n] = ($str1[$n] ?? '') !== ($str2[$n] ?? '');
        }

        return $diffs;
    }
}
