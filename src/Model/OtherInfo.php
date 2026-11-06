<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_other_info table.
 */
final class OtherInfo
{
    public function __construct(
        public readonly string $specialSkills = '',
        public readonly string $recognition = '',
        public readonly string $membershipOrganization = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            specialSkills: Text::blank($row['special_skills'] ?? ''),
            recognition: Text::blank($row['recognition'] ?? ''),
            membershipOrganization: Text::blank($row['membership_organization'] ?? ''),
        );
    }
}
