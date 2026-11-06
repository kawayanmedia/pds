<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Config;

/**
 * A repeating block of rows: where it starts, how tall each row is, where the
 * taller rows sit and when a template page has to be repeated.
 */
final class TableLayout
{
    /**
     * @param list<Placement>     $columns          y is relative to the row
     * @param array<int, float>   $rowHeights       row number => height, overriding $rowHeight
     * @param float               $endY             last y a row may start at before the template page repeats
     * @param list<int>           $pageBreakAfter   continue on a new page after these row numbers
     */
    public function __construct(
        public readonly array $columns,
        public readonly float $startY,
        public readonly float $rowHeight,
        public readonly array $rowHeights = [],
        public readonly float $endY = 0.0,
        public readonly array $pageBreakAfter = [],
        public readonly string $templatePage = 'page1',
    ) {
    }

    public function heightFor(int $rowNumber): float
    {
        return $this->rowHeights[$rowNumber] ?? $this->rowHeight;
    }

    public function breaksAfter(int $rowNumber): bool
    {
        return in_array($rowNumber, $this->pageBreakAfter, true);
    }
}
