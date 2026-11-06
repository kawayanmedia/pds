<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer\Page;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\Model\OtherInfo;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Model\Training;
use KawayanMedia\Pds\Model\VolunteerWork;
use KawayanMedia\Pds\Renderer\TableWriter;
use setasign\Fpdi\Fpdi;

/**
 * Page 3: voluntary work, learning and development, other information.
 *
 * Other information is printed before the trainings, exactly as the legacy
 * controller did, so a training overflow page never leaves it behind.
 */
final class LearningPage
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
            $this->layout->volunteer,
            array_map(static fn (VolunteerWork $row): array => [
                'name' => $row->organization,
                'inclusive_date_from' => $row->dateFrom,
                'inclusive_date_to' => $row->dateTo,
                'number_of_hours' => $row->numberOfHours,
                'position' => $row->position,
            ], $pds->volunteer),
            $newPage,
        );

        $this->tables->write(
            $pdf,
            $this->layout->otherInfo,
            array_map(static fn (OtherInfo $row): array => [
                'special_skills' => $row->specialSkills,
                'recognition' => $row->recognition,
                'membership_organization' => $row->membershipOrganization,
            ], $pds->otherInfo),
            $newPage,
        );

        $this->tables->write(
            $pdf,
            $this->layout->training,
            array_map(static fn (Training $row): array => [
                'name' => $row->name,
                'date_from' => $row->dateFrom,
                'date_to' => $row->dateTo,
                'number_hours' => $row->numberOfHours,
                'conducted_by' => $row->conductedBy,
            ], $pds->training),
            $newPage,
        );
    }
}
