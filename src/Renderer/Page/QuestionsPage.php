<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer\Page;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\Config\Placement;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Model\Reference;
use KawayanMedia\Pds\Renderer\PlacementWriter;
use KawayanMedia\Pds\Renderer\TableWriter;
use KawayanMedia\Pds\Support\Dates;
use setasign\Fpdi\Fpdi;

/**
 * Page 4: the ten civil service questions, references and the CTC block.
 */
final class QuestionsPage
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
    public function draw(Fpdi $pdf, Pds $pds, PdsOptions $options, callable $newPage): void
    {
        $this->drawAnswers($pdf, $pds);

        $this->tables->write(
            $pdf,
            $this->layout->references,
            array_map(static fn (Reference $row): array => [
                'name' => $row->name,
                'address' => $row->address,
                'tel_no' => $row->telephone,
            ], $pds->references),
            $newPage,
        );

        $this->drawCtc($pdf, $pds, $options);
    }

    /**
     * Each question has its own absolute y, so a missing answer cannot shift
     * the rows below it (the legacy controller advanced the cursor per answer).
     */
    private function drawAnswers(Fpdi $pdf, Pds $pds): void
    {
        $block = $this->layout->questions;
        $positions = (array) ($block['y'] ?? []);

        foreach ($positions as $number => $y) {
            $answer = $pds->answer((int) $number);

            if ($answer === null) {
                continue;
            }

            $x = $answer ? (float) $block['yesX'] : (float) $block['noX'];

            $this->placements->write($pdf, ['answer' => 'X'], Placement::text('answer', $x, (float) $y));
        }
    }

    private function drawCtc(Fpdi $pdf, Pds $pds, PdsOptions $options): void
    {
        $block = $this->layout->ctc;
        $x = (float) ($block['x'] ?? 15.0);
        $y = (float) ($block['y'] ?? 275.0);
        $span = (float) ($block['lineSpan'] ?? 13.0);
        $reference = $pds->firstReference();

        $values = [
            'ctc_no' => $reference->ctcNumber ?? '',
            'issue_at' => $reference->issuedAt ?? '',
            'issue_on' => Dates::slashDate($reference->issuedOn ?? null),
            'accomplished_on' => $options->accomplishedOn()->format($options->dateFormat),
        ];

        $this->writeValue($pdf, 'ctc_no', $values['ctc_no'], $x, $y);
        $this->writeValue($pdf, 'issue_at', $values['issue_at'], $x, $y + $span);
        $this->writeValue($pdf, 'issue_on', $values['issue_on'], $x, $y + ($span * 2));
        $this->writeValue($pdf, 'accomplished_on', $values['accomplished_on'], (float) ($block['dateX'] ?? 90.0), $y + ($span * 2));
    }

    private function writeValue(Fpdi $pdf, string $key, string $value, float $x, float $y): void
    {
        $this->placements->write($pdf, [$key => $value], Placement::text($key, $x, $y));
    }
}
