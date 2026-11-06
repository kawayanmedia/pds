<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Dates;
use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_organization table (voluntary work section).
 */
final class VolunteerWork
{
    public function __construct(
        public readonly string $organization = '',
        public readonly mixed $dateFrom = null,
        public readonly mixed $dateTo = null,
        public readonly string $numberOfHours = '',
        public readonly string $position = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            organization: Text::blank($row['name'] ?? ''),
            dateFrom: $row['inclusive_date_from'] ?? null,
            dateTo: $row['inclusive_date_to'] ?? null,
            numberOfHours: Text::blank($row['number_of_hours'] ?? ''),
            position: Text::blank($row['position'] ?? ''),
        );
    }

    public function dateFromLabel(): string
    {
        return Dates::slashDate($this->dateFrom);
    }

    public function dateToLabel(): string
    {
        return Dates::slashDate($this->dateTo);
    }
}
