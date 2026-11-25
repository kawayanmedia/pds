<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * The position details screen: the employment columns of the employee record,
 * which the PDS only updates (it never creates an employee).
 *
 * The host schema is honoured: `employment_type_id` holds the legacy
 * "permanent" employment type key and `date_hired` holds the first day of
 * service.
 */
final class PdsProfile
{
    /**
     * Columns written by {@see \KawayanMedia\Pds\Service\PdsEditor::savePositionDetails()}.
     *
     * @var list<string>
     */
    public const COLUMNS = [
        'item_number',
        'position',
        'office_id',
        'salary_grade',
        'step',
        'employment_type_id',
        'last_promotion',
        'last_increment',
        'level',
        'eligibility',
        'date_hired',
        'graduated',
        'course',
        'units',
        'post_grad',
    ];

    /**
     * @param mixed $officeId       numeric office key or code, as stored by the host
     * @param mixed $permanent      employment type key, stored in `employment_type_id`
     * @param mixed $lastPromotion  date or free text, as stored by the host
     * @param mixed $lastIncrement  date or free text, as stored by the host
     */
    public function __construct(
        public readonly mixed $itemNumber = null,
        public readonly string $position = '',
        public readonly mixed $officeId = null,
        public readonly mixed $salaryGrade = null,
        public readonly mixed $step = null,
        public readonly mixed $permanent = null,
        public readonly mixed $lastPromotion = null,
        public readonly mixed $lastIncrement = null,
        public readonly mixed $level = null,
        public readonly string $eligibility = '',
        public readonly mixed $firstDayOfService = null,
        public readonly string $graduated = '',
        public readonly string $course = '',
        public readonly mixed $units = null,
        public readonly string $postGrad = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            itemNumber: $row['item_number'] ?? null,
            position: Text::blank($row['position'] ?? ''),
            officeId: $row['office_id'] ?? null,
            salaryGrade: $row['salary_grade'] ?? null,
            step: $row['step'] ?? null,
            permanent: $row['employment_type_id'] ?? null,
            lastPromotion: $row['last_promotion'] ?? null,
            lastIncrement: $row['last_increment'] ?? null,
            level: $row['level'] ?? null,
            eligibility: Text::blank($row['eligibility'] ?? ''),
            firstDayOfService: $row['date_hired'] ?? null,
            graduated: Text::blank($row['graduated'] ?? ''),
            course: Text::blank($row['course'] ?? ''),
            units: $row['units'] ?? null,
            postGrad: Text::blank($row['post_grad'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'item_number' => $this->itemNumber,
            'position' => $this->position,
            'office_id' => $this->officeId,
            'salary_grade' => $this->salaryGrade,
            'step' => $this->step,
            'employment_type_id' => $this->permanent,
            'last_promotion' => $this->lastPromotion,
            'last_increment' => $this->lastIncrement,
            'level' => $this->level,
            'eligibility' => $this->eligibility,
            'date_hired' => $this->firstDayOfService,
            'graduated' => $this->graduated,
            'course' => $this->course,
            'units' => $this->units,
            'post_grad' => $this->postGrad,
        ];
    }
}
