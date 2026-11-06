<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Dates;
use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_work_experience table.
 */
final class WorkExperience
{
    public const PRESENT = 'Present';

    public function __construct(
        public readonly mixed $dateFrom = null,
        public readonly mixed $dateTo = null,
        public readonly string $position = '',
        public readonly string $company = '',
        public readonly string $monthlySalary = '',
        public readonly string $salaryGrade = '',
        public readonly string $status = '',
        public readonly bool $governmentService = false,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            dateFrom: $row['inclusive_date_from'] ?? null,
            dateTo: $row['inclusive_date_to'] ?? null,
            position: Text::blank($row['position'] ?? ''),
            company: Text::blank($row['company'] ?? ''),
            monthlySalary: Text::blank($row['monthly_salary'] ?? ''),
            salaryGrade: Text::blank($row['salary_grade'] ?? ''),
            status: Text::blank($row['status'] ?? ''),
            governmentService: self::isYes($row['govt_service'] ?? false),
        );
    }

    public function dateFromLabel(): string
    {
        return Dates::slashDate($this->dateFrom);
    }

    public function dateToLabel(string $presentLabel = self::PRESENT): string
    {
        if (Dates::parse($this->dateTo) === null) {
            return self::isPresent($this->dateTo) ? $presentLabel : '';
        }

        return Dates::slashDate($this->dateTo);
    }

    public function governmentServiceLabel(): string
    {
        return $this->governmentService ? 'Yes' : 'No';
    }

    private static function isPresent(mixed $value): bool
    {
        return is_string($value) && strcasecmp(trim($value), 'present') === 0;
    }

    private static function isYes(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true', 'on'], true);
    }
}
