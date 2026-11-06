<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\Placement;
use KawayanMedia\Pds\Support\BoxText;
use KawayanMedia\Pds\Support\Dates;
use KawayanMedia\Pds\Support\Text;
use KawayanMedia\Pds\Support\WordChunks;
use setasign\Fpdi\Fpdi;

/**
 * Draws a single placement: the write-side counterpart of Config\Placement.
 */
final class PlacementWriter
{
    public function __construct(private readonly PdsLayout $layout)
    {
    }

    /**
     * @param array<string, mixed> $values
     */
    public function write(Fpdi $pdf, array $values, Placement $placement, float $yOffset = 0.0): void
    {
        $y = $placement->y + $yOffset;

        $this->selectFont($pdf, $placement);

        match ($placement->mode) {
            Placement::BOXED => $this->writeBoxed($pdf, $values[$placement->key] ?? '', $placement, $y),
            Placement::DATE => $this->writeLine(
                $pdf,
                $placement->x,
                $y,
                Dates::format($values[$placement->key] ?? null, $placement->format),
            ),
            Placement::TWO_LINE => $this->writeTwoLine($pdf, $values[$placement->key] ?? '', $placement, $y),
            Placement::CHECK => $this->writeCheck($pdf, $values[$placement->key] ?? '', $placement, $y),
            default => $this->writeLine($pdf, $placement->x, $y, $this->text($values[$placement->key] ?? '', $placement)),
        };
    }

    public function selectFont(Fpdi $pdf, ?Placement $placement = null): void
    {
        $size = $placement?->fontSize ?? $this->layout->baseFontSize;

        $pdf->SetTextColor(...$this->layout->textColor);
        $pdf->SetFont($this->layout->fontFamily, '', $size);
    }

    private function writeBoxed(Fpdi $pdf, mixed $value, Placement $placement, float $y): void
    {
        $boxes = BoxText::boxes(Text::blank($value), count($placement->boxX));

        $pdf->SetXY($placement->boxX[0] ?? $placement->x, $y);

        foreach ($placement->boxX as $index => $x) {
            if ($index > 0) {
                $pdf->SetX($x);
            }

            $pdf->Write(0, $boxes[$index]);
        }
    }

    private function writeTwoLine(Fpdi $pdf, mixed $value, Placement $placement, float $y): void
    {
        [$first, $second] = WordChunks::splitOverflow(Text::blank($value), $placement->wrapWidth);

        $this->writeLine($pdf, $placement->x, $y, $first);

        if ($second !== '') {
            $this->writeLine($pdf, $placement->x, $y + $placement->secondLineOffset, $second);
        }
    }

    private function writeCheck(Fpdi $pdf, mixed $value, Placement $placement, float $y): void
    {
        if ($placement->equals === null) {
            return;
        }

        if (strcasecmp(Text::blank($value), $placement->equals) !== 0) {
            return;
        }

        $this->writeLine($pdf, $placement->x, $y, 'X');
    }

    private function writeLine(Fpdi $pdf, float $x, float $y, string $value): void
    {
        if ($value === '') {
            return;
        }

        $pdf->SetXY($x, $y);
        $pdf->Write(0, Text::latin1($value));
    }

    private function text(mixed $value, Placement $placement): string
    {
        $text = Text::blank($value);

        if ($placement->titleCase) {
            $text = Text::titleCase($text);
        }

        if ($placement->maxChars > 0) {
            $text = Text::limitForPdf($text, $placement->maxChars);
        }

        return $text;
    }
}
