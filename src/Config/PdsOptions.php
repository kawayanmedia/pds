<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Config;

use DateTimeImmutable;
use KawayanMedia\Pds\Model\WorkExperience;
use KawayanMedia\Pds\Support\Dates;

/**
 * Per-render switches that are not coordinates.
 */
final class PdsOptions
{
    /**
     * @param list<int> $pages which of the four form pages to print
     */
    public function __construct(
        public readonly array $pages = [1, 2, 3, 4],
        public readonly mixed $accomplishedOn = null,
        public readonly string $dateFormat = Dates::LONG_DATE,
        public readonly string $presentLabel = WorkExperience::PRESENT,
    ) {
    }

    public function includes(int $page): bool
    {
        return in_array($page, $this->pages, true);
    }

    /**
     * The date printed at the bottom of page 4 ("Date Accomplished").
     */
    public function accomplishedOn(): DateTimeImmutable
    {
        return Dates::parse($this->accomplishedOn) ?? new DateTimeImmutable();
    }
}
