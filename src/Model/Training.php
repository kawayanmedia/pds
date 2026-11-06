<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Dates;
use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_training table.
 */
final class Training
{
    public function __construct(
        public readonly string $name = '',
        public readonly mixed $dateFrom = null,
        public readonly mixed $dateTo = null,
        public readonly string $numberOfHours = '',
        public readonly string $conductedBy = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            name: Text::blank($row['name'] ?? ''),
            dateFrom: $row['date_from'] ?? null,
            dateTo: $row['date_to'] ?? null,
            numberOfHours: Text::blank($row['number_hours'] ?? ''),
            conductedBy: Text::blank($row['conducted_by'] ?? ''),
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
