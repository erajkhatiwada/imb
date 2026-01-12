<?php

declare(strict_types=1);

namespace Imb;

/**
 * Result of a barcode decode operation.
 *
 * Contains the decoded data plus optional repair information
 * if the barcode was damaged but recoverable.
 */
final class DecodeResult
{
    /** @var IMBData The decoded IMB data */
    public $data;

    /** @var string|null Optional message (e.g., "Damaged barcode") */
    public $message;

    /** @var string|null Suggested corrected barcode string */
    public $suggest;

    /** @var array<int, bool>|null Array indicating which positions differ */
    public $highlight;

    /**
     * @param IMBData $data The decoded IMB data
     * @param string|null $message Optional message (e.g., "Damaged barcode")
     * @param string|null $suggest Suggested corrected barcode string
     * @param array<int, bool>|null $highlight Array indicating which positions differ
     */
    public function __construct(
        $data,
        $message = null,
        $suggest = null,
        $highlight = null
    ) {
        $this->data = $data;
        $this->message = $message;
        $this->suggest = $suggest;
        $this->highlight = $highlight;
    }

    /**
     * Check if the barcode was damaged but repaired.
     *
     * @return bool
     */
    public function wasDamaged()
    {
        return $this->message !== null && strpos($this->message, 'Damaged') !== false;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = $this->data->toArray();

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        if ($this->suggest !== null) {
            $result['suggest'] = $this->suggest;
        }

        if ($this->highlight !== null) {
            $result['highlight'] = $this->highlight;
        }

        return $result;
    }
}
