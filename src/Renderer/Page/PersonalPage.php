<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer\Page;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\Placement;
use KawayanMedia\Pds\Model\EducationLevel;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Renderer\PlacementWriter;
use KawayanMedia\Pds\Renderer\TableWriter;
use KawayanMedia\Pds\Support\Text;
use KawayanMedia\Pds\Support\WordChunks;
use setasign\Fpdi\Fpdi;

/**
 * Page 1: personal information, family background, children and education.
 */
final class PersonalPage
{
    public function __construct(
        private readonly PdsLayout $layout,
        private readonly PlacementWriter $placements,
        private readonly TableWriter $tables,
    ) {
    }

    /**
     * @param callable(string): void $newPage
     */
    public function draw(Fpdi $pdf, Pds $pds, callable $newPage): void
    {
        $values = $pds->family === null
            ? $pds->personal->toValues()
            : $pds->personal->toValues() + $pds->family->toValues();

        foreach ($this->layout->personalPage->placements as $placement) {
            $this->placements->write($pdf, $values, $placement);
        }

        $this->drawChildren($pdf, $pds, $newPage);
        $this->drawEducation($pdf, $pds);
    }

    /**
     * @param callable(string): void $newPage
     */
    private function drawChildren(Fpdi $pdf, Pds $pds, callable $newPage): void
    {
        if ($pds->children === []) {
            return;
        }

        $rows = array_map(static fn ($child): array => [
            'name' => $child->name,
            'birth_date' => $child->birthDate,
        ], $pds->children);

        $this->tables->write($pdf, $this->layout->children, $rows, $newPage);
    }

    private function drawEducation(Fpdi $pdf, Pds $pds): void
    {
        $block = $this->layout->education;
        $lines = (int) ($block['lines'] ?? 4);
        $lineHeight = (float) ($block['lineHeight'] ?? 3.0);
        $fontSize = (float) ($block['fontSize'] ?? 10.0);

        foreach (EducationLevel::cases() as $level) {
            $entries = $pds->educationFor($level);

            if ($entries === []) {
                continue;
            }

            $y = (float) ($block['anchors'][$level->value] ?? 0.0);

            foreach ($entries as $entry) {
                $school = WordChunks::lines(Text::titleCase($entry->schoolName), (int) $block['schoolWidth'], $lines);
                $degree = WordChunks::lines(Text::titleCase($entry->degreeCourse), (int) $block['degreeWidth'], $lines);

                for ($line = 0; $line < $lines; $line++) {
                    $lineY = $y + ($line * $lineHeight);

                    $this->writeValue($pdf, 'school', $school[$line], (float) $block['schoolX'], $lineY, $fontSize);
                    $this->writeValue($pdf, 'degree', $degree[$line], (float) $block['degreeX'], $lineY, $fontSize);

                    if ($line > 0) {
                        continue;
                    }

                    $this->writeValue($pdf, 'year', $entry->yearGraduated, (float) $block['yearX'], $lineY, $fontSize);
                    $this->writeValue($pdf, 'grade', $entry->highestGrade, (float) $block['gradeX'], $lineY, $fontSize);
                    $this->writeValue($pdf, 'from', $entry->attendFrom, (float) $block['fromX'], $lineY, $fontSize);
                    $this->writeValue($pdf, 'to', $entry->attendTo, (float) $block['toX'], $lineY, $fontSize);
                    $this->writeValue($pdf, 'scholarship', $entry->scholarship, (float) $block['scholarshipX'], $lineY, $fontSize);
                }

                $y += $lines * $lineHeight;
            }
        }
    }

    private function writeValue(Fpdi $pdf, string $key, mixed $value, float $x, float $y, float $fontSize): void
    {
        $this->placements->write($pdf, [$key => $value], Placement::text($key, $x, $y, $fontSize));
    }
}
