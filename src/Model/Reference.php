<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_references table.
 */
final class Reference
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $address = '',
        public readonly string $telephone = '',
        public readonly string $ctcNumber = '',
        public readonly string $issuedAt = '',
        public readonly mixed $issuedOn = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            name: Text::blank($row['name'] ?? ''),
            address: Text::blank($row['address'] ?? ''),
            telephone: Text::blank($row['tel_no'] ?? ''),
            ctcNumber: Text::blank($row['ctc_no'] ?? ''),
            issuedAt: Text::blank($row['issue_at'] ?? ''),
            issuedOn: $row['issue_on'] ?? null,
        );
    }
}
