<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Dates;
use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_eligibility table.
 */
final class Eligibility
{
    public function __construct(
        public readonly string $type = '',
        public readonly string $rating = '',
        public readonly mixed $dateExamConferment = null,
        public readonly string $placeExamConferment = '',
        public readonly string $licenseNumber = '',
        public readonly mixed $licenseReleaseDate = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            type: Text::blank($row['type'] ?? ''),
            rating: Text::blank($row['rating'] ?? ''),
            dateExamConferment: $row['date_exam_conferment'] ?? null,
            placeExamConferment: Text::blank($row['place_exam_conferment'] ?? ''),
            licenseNumber: Text::blank($row['license_no'] ?? ''),
            licenseReleaseDate: $row['license_release_date'] ?? null,
        );
    }

    public function confermentDateLabel(): string
    {
        return Dates::slashDate($this->dateExamConferment);
    }

    public function releaseDateLabel(): string
    {
        return Dates::slashDate($this->licenseReleaseDate);
    }
}
