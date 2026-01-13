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
    /** @var string 2-digit barcode identifier (required) */
    public $barcodeId;

    /** @var string 3-digit service type (required) */
    public $serviceType;

    /** @var string 6 or 9 digit USPS mailer ID (required) */
    public $mailerId;

    /** @var string Serial number (required, length depends on mailerId) */
    public $serialNum;

    /** @var string|null 5-digit ZIP code (optional) */
    public $zip;

    /** @var string|null 4-digit ZIP+4 extension (optional) */
    public $plus4;

    /** @var string|null 2-digit delivery point (optional) */
    public $deliveryPt;

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
        $barcodeId,
        $serviceType,
        $mailerId,
        $serialNum,
        $zip = null,
        $plus4 = null,
        $deliveryPt = null
    ) {
        $this->barcodeId = $barcodeId;
        $this->serviceType = $serviceType;
        $this->mailerId = $mailerId;
        $this->serialNum = $serialNum;
        $this->zip = $zip;
        $this->plus4 = $plus4;
        $this->deliveryPt = $deliveryPt;
    }

    /**
     * Create from an associative array.
     *
     * @param array<string, string|null> $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        return new self(
            $data['barcode_id'] ?? $data['barcodeId'] ?? '',
            $data['service_type'] ?? $data['serviceType'] ?? '',
            $data['mailer_id'] ?? $data['mailerId'] ?? '',
            $data['serial_num'] ?? $data['serialNum'] ?? '',
            $data['zip'] ?? null,
            $data['plus4'] ?? null,
            $data['delivery_pt'] ?? $data['deliveryPt'] ?? null
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

    /**
     * Convert to a concatenated string of all field values.
     *
     * Returns the raw numeric string representation of the IMB data,
     * e.g., "0027010350201795597150310160515"
     *
     * @return string
     */
    public function stringify(): string
    {
        return $this->barcodeId
            . $this->serviceType
            . $this->mailerId
            . $this->serialNum
            . ($this->zip ?? '')
            . ($this->plus4 ?? '')
            . ($this->deliveryPt ?? '');
    }
}
