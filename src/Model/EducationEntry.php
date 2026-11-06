<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_education_background table.
 */
final class EducationEntry
{
    public function __construct(
        public readonly EducationLevel $level = EducationLevel::College,
        public readonly string $schoolName = '',
        public readonly string $degreeCourse = '',
        public readonly string $yearGraduated = '',
        public readonly string $highestGrade = '',
        public readonly string $attendFrom = '',
        public readonly string $attendTo = '',
        public readonly string $scholarship = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            level: EducationLevel::fromValue($row['level'] ?? null) ?? EducationLevel::College,
            schoolName: Text::blank($row['school_name'] ?? ''),
            degreeCourse: Text::blank($row['degree_course'] ?? ''),
            yearGraduated: Text::blank($row['year_graduated'] ?? ''),
            highestGrade: Text::blank($row['highest_grade'] ?? ''),
            attendFrom: Text::blank($row['attend_from'] ?? ''),
            attendTo: Text::blank($row['attend_to'] ?? ''),
            scholarship: Text::blank($row['scholarship'] ?? ''),
        );
    }
}
