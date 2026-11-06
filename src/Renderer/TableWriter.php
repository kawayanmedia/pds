<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer;

use KawayanMedia\Pds\Config\TableLayout;
use setasign\Fpdi\Fpdi;

/**
 * Walks a repeating block of rows, applying the row height of each row and
 * starting a fresh copy of the template page when the block runs out of space.
 */
final class TableWriter
{
    public function __construct(private readonly PlacementWriter $placements)
    {
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param callable(string): void     $newPage receives the template page key
     *
     * @return float y position after the last row
     */
    public function write(Fpdi $pdf, TableLayout $table, array $rows, callable $newPage): float
    {
        $y = $table->startY;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            if ($table->endY > 0.0 && $y > $table->endY) {
                $newPage($table->templatePage);
                $y = $table->startY;
            }

            foreach ($table->columns as $placement) {
                $this->placements->write($pdf, $row, $placement, $y);
            }

            $y += $table->heightFor($rowNumber);

            if ($table->breaksAfter($rowNumber)) {
                $newPage($table->templatePage);
                $y = $table->startY;
            }
        }

        return $y;
    }
}
