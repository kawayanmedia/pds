<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_family_background table.
 */
final class FamilyBackground
{
    public function __construct(
        public readonly string $spouseLastName = '',
        public readonly string $spouseFirstName = '',
        public readonly string $spouseMiddleName = '',
        public readonly string $spouseOccupation = '',
        public readonly string $spouseEmployer = '',
        public readonly string $spouseBusinessAddress = '',
        public readonly string $spouseTelephone = '',
        public readonly string $fatherLastName = '',
        public readonly string $fatherFirstName = '',
        public readonly string $fatherMiddleName = '',
        public readonly string $motherLastName = '',
        public readonly string $motherFirstName = '',
        public readonly string $motherMiddleName = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            spouseLastName: Text::blank($row['spouse_lname'] ?? ''),
            spouseFirstName: Text::blank($row['spouse_fname'] ?? ''),
            spouseMiddleName: Text::blank($row['spouse_mname'] ?? ''),
            spouseOccupation: Text::blank($row['spouse_occupation'] ?? ''),
            spouseEmployer: Text::blank($row['spouse_employer'] ?? ''),
            spouseBusinessAddress: Text::blank($row['spouse_biz_ad'] ?? ''),
            spouseTelephone: Text::blank($row['spouse_tel'] ?? ''),
            fatherLastName: Text::blank($row['father_lname'] ?? ''),
            fatherFirstName: Text::blank($row['father_fname'] ?? ''),
            fatherMiddleName: Text::blank($row['father_mname'] ?? ''),
            motherLastName: Text::blank($row['mother_lname'] ?? ''),
            motherFirstName: Text::blank($row['mother_fname'] ?? ''),
            motherMiddleName: Text::blank($row['mother_mname'] ?? ''),
        );
    }

    /**
     * Legacy column names, so the page layout can reference them directly.
     *
     * @return array<string, mixed>
     */
    public function toValues(): array
    {
        return [
            'spouse_lname' => $this->spouseLastName,
            'spouse_fname' => $this->spouseFirstName,
            'spouse_mname' => $this->spouseMiddleName,
            'spouse_occupation' => $this->spouseOccupation,
            'spouse_employer' => $this->spouseEmployer,
            'spouse_biz_ad' => $this->spouseBusinessAddress,
            'spouse_tel' => $this->spouseTelephone,
            'father_lname' => $this->fatherLastName,
            'father_fname' => $this->fatherFirstName,
            'father_mname' => $this->fatherMiddleName,
            'mother_lname' => $this->motherLastName,
            'mother_fname' => $this->motherFirstName,
            'mother_mname' => $this->motherMiddleName,
        ];
    }
}
