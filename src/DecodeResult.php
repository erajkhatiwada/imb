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
    /**
     * @param IMBData $data The decoded IMB data
     * @param string|null $message Optional message (e.g., "Damaged barcode")
     * @param string|null $suggest Suggested corrected barcode string
     * @param array<int, bool>|null $highlight Array indicating which positions differ
     */
    public function __construct(
        public IMBData $data,
        public ?string $message = null,
        public ?string $suggest = null,
        public ?array $highlight = null
    ) {
    }

    /**
     * Check if the barcode was damaged but repaired.
     *
     * @return bool
     */
    public function wasDamaged(): bool
    {
        return $this->message !== null && str_contains($this->message, 'Damaged');
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
