<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Text;

/**
 * One row of the legacy `service_record` table, as written by the PDS service
 * record screen.
 */
final class ServiceRecordEntry
{
    /**
     * @var list<string>
     */
    public const COLUMNS = [
        'date_from',
        'date_to',
        'designation',
        'status',
        'salary',
        'office_entity',
        'lwop',
        'branch',
        'remarks',
        'separation_date',
        'separation_cause',
    ];

    public function __construct(
        public readonly string $dateFrom = '',
        public readonly string $dateTo = '',
        public readonly string $designation = '',
        public readonly string $status = '',
        public readonly string $salary = '',
        public readonly string $officeEntity = '',
        public readonly string $lwop = '',
        public readonly string $branch = '',
        public readonly string $remarks = '',
        public readonly string $separationDate = '',
        public readonly string $separationCause = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            dateFrom: Text::blank($row['date_from'] ?? ''),
            dateTo: Text::blank($row['date_to'] ?? ''),
            designation: Text::blank($row['designation'] ?? ''),
            status: Text::blank($row['status'] ?? ''),
            salary: Text::blank($row['salary'] ?? ''),
            officeEntity: Text::blank($row['office_entity'] ?? ''),
            lwop: Text::blank($row['lwop'] ?? ''),
            branch: Text::blank($row['branch'] ?? ''),
            remarks: Text::blank($row['remarks'] ?? ''),
            separationDate: Text::blank($row['separation_date'] ?? ''),
            separationCause: Text::blank($row['separation_cause'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'designation' => $this->designation,
            'status' => $this->status,
            'salary' => $this->salary,
            'office_entity' => $this->officeEntity,
            'lwop' => $this->lwop,
            'branch' => $this->branch,
            'remarks' => $this->remarks,
            'separation_date' => $this->separationDate,
            'separation_cause' => $this->separationCause,
        ];
    }
}
