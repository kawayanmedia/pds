<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer\Page;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\Model\Eligibility;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Model\WorkExperience;
use KawayanMedia\Pds\Renderer\TableWriter;
use setasign\Fpdi\Fpdi;

/**
 * Page 2: civil service eligibility and work experience.
 */
final class CareerPage
{
    public function __construct(
        private readonly PdsLayout $layout,
        private readonly TableWriter $tables,
    ) {
    }

    /**
     * @param callable(string): void $newPage
     */
    public function draw(Fpdi $pdf, Pds $pds, PdsOptions $options, callable $newPage): void
    {
        $this->tables->write(
            $pdf,
            $this->layout->eligibility,
            array_map(static fn (Eligibility $row): array => [
                'type' => $row->type,
                'rating' => $row->rating,
                'date_exam_conferment' => $row->dateExamConferment,
                'place_exam_conferment' => $row->placeExamConferment,
                'license_no' => $row->licenseNumber,
                'license_release_date' => $row->licenseReleaseDate,
            ], $pds->eligibility),
            $newPage,
        );

        $this->tables->write(
            $pdf,
            $this->layout->work,
            array_map(static fn (WorkExperience $row): array => [
                'inclusive_date_from' => $row->dateFrom,
                'inclusive_date_to' => $row->dateToLabel($options->presentLabel),
                'position' => $row->position,
                'company' => $row->company,
                'monthly_salary' => $row->monthlySalary,
                'salary_grade' => $row->salaryGrade,
                'status' => $row->status,
                'govt_service' => $row->governmentServiceLabel(),
            ], $pds->work),
            $newPage,
        );
    }
}
