<?php

declare(strict_types=1);

namespace Imb;

/**
 * Data Transfer Object for Intelligent Mail Barcode fields.
 *
 * Represents all the fields that can be encoded into or decoded from
 * a USPS Intelligent Mail Barcode.
 */
final class IMBData
{
    /**
     * @param string $barcodeId 2-digit barcode identifier (required)
     * @param string $serviceType 3-digit service type (required)
     * @param string $mailerId 6 or 9 digit USPS mailer ID (required)
     * @param string $serialNum Serial number (required, length depends on mailerId)
     * @param string|null $zip 5-digit ZIP code (optional)
     * @param string|null $plus4 4-digit ZIP+4 extension (optional)
     * @param string|null $deliveryPt 2-digit delivery point (optional)
     */
    public function __construct(
        public string $barcodeId,
        public string $serviceType,
        public string $mailerId,
        public string $serialNum,
        public ?string $zip = null,
        public ?string $plus4 = null,
        public ?string $deliveryPt = null
    ) {
    }

    /**
     * Create from an associative array.
     *
     * @param array<string, string|null> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            barcodeId: $data['barcode_id'] ?? $data['barcodeId'] ?? '',
            serviceType: $data['service_type'] ?? $data['serviceType'] ?? '',
            mailerId: $data['mailer_id'] ?? $data['mailerId'] ?? '',
            serialNum: $data['serial_num'] ?? $data['serialNum'] ?? '',
            zip: $data['zip'] ?? null,
            plus4: $data['plus4'] ?? null,
            deliveryPt: $data['delivery_pt'] ?? $data['deliveryPt'] ?? null
        );
    }

    /**
     * Convert to associative array.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        $result = [
            'barcode_id' => $this->barcodeId,
            'service_type' => $this->serviceType,
            'mailer_id' => $this->mailerId,
            'serial_num' => $this->serialNum,
        ];

        if ($this->zip !== null) {
            $result['zip'] = $this->zip;
        }

        if ($this->plus4 !== null) {
            $result['plus4'] = $this->plus4;
        }

        if ($this->deliveryPt !== null) {
            $result['delivery_pt'] = $this->deliveryPt;
        }

        return $result;
    }

    /**
     * Get the mailer ID length (6 or 9 digits).
     *
     * @return int
     */
    public function getMailerIdLength(): int
    {
        return strlen($this->mailerId);
    }

    /**
     * Check if this is a 9-digit mailer ID.
     *
     * @return bool
     */
    public function hasNineDigitMailerId(): bool
    {
        return strlen($this->mailerId) === 9;
    }
}
