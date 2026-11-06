<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Renderer;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Renderer\Page\CareerPage;
use KawayanMedia\Pds\Renderer\Page\LearningPage;
use KawayanMedia\Pds\Renderer\Page\PersonalPage;
use KawayanMedia\Pds\Renderer\Page\QuestionsPage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

/**
 * Draws the four pages of the CS Form 212 onto the configured templates.
 *
 * One Pds becomes four pages inside one document; nothing is written to disk
 * unless save() is used.
 */
final class PdsPdfRenderer
{
    private readonly PlacementWriter $placements;

    private readonly TableWriter $tables;

    private readonly PersonalPage $personalPage;

    private readonly CareerPage $careerPage;

    private readonly LearningPage $learningPage;

    private readonly QuestionsPage $questionsPage;

    public function __construct(
        private readonly PdsLayout $layout,
        private readonly PdsOptions $options = new PdsOptions(),
    ) {
        $this->placements = new PlacementWriter($layout);
        $this->tables = new TableWriter($this->placements);
        $this->personalPage = new PersonalPage($layout, $this->placements, $this->tables);
        $this->careerPage = new CareerPage($layout, $this->tables);
        $this->learningPage = new LearningPage($layout, $this->tables);
        $this->questionsPage = new QuestionsPage($layout, $this->placements, $this->tables);
    }

    public function layout(): PdsLayout
    {
        return $this->layout;
    }

    public function options(): PdsOptions
    {
        return $this->options;
    }

    public function withOptions(PdsOptions $options): self
    {
        return new self($this->layout, $options);
    }

    public function render(Pds $pds): string
    {
        return $this->renderMany([$pds]);
    }

    /**
     * @param list<Pds> $documents one four-page form per employee
     */
    public function renderMany(array $documents): string
    {
        return $this->output($documents, '', OutputMode::String);
    }

    public function save(Pds|array $documents, string $path): void
    {
        $this->output($this->normalize($documents), $path, OutputMode::File);
    }

    public function stream(Pds|array $documents, string $filename = 'pds.pdf'): void
    {
        $this->output($this->normalize($documents), $filename, OutputMode::Inline);
    }

    public function download(Pds|array $documents, string $filename = 'pds.pdf'): void
    {
        $this->output($this->normalize($documents), $filename, OutputMode::Download);
    }

    /**
     * @param list<Pds> $documents
     */
    public function output(array $documents, string $filename, OutputMode $mode): string
    {
        $this->assertTemplatesExist();

        $pdf = $this->newDocument();

        foreach ($documents as $document) {
            $this->draw($pdf, $document);
        }

        $result = $pdf->Output($mode->value, $filename);

        return is_string($result) ? $result : '';
    }

    private function newDocument(): Fpdi
    {
        $pdf = new Fpdi(
            $this->layout->pageOrientation !== '' ? $this->layout->pageOrientation : 'P',
            $this->layout->pageUnit,
            $this->layout->pageSize !== '' ? $this->layout->pageSize : 'Legal',
        );

        $pdf->SetCompression($this->layout->compress);

        return $pdf;
    }

    private function draw(Fpdi $pdf, Pds $pds): void
    {
        $newPage = function (string $templatePage) use ($pdf): void {
            $this->addTemplatePage($pdf, $templatePage);
        };

        if ($this->options->includes(1)) {
            $this->addTemplatePage($pdf, PdsLayout::PAGE_PERSONAL);
            $this->personalPage->draw($pdf, $pds, $newPage);
        }

        if ($this->options->includes(2)) {
            $this->addTemplatePage($pdf, PdsLayout::PAGE_CAREER);
            $this->careerPage->draw($pdf, $pds, $this->options, $newPage);
        }

        if ($this->options->includes(3)) {
            $this->addTemplatePage($pdf, PdsLayout::PAGE_LEARNING);
            $this->learningPage->draw($pdf, $pds, $this->options, $newPage);
        }

        if ($this->options->includes(4)) {
            $this->addTemplatePage($pdf, PdsLayout::PAGE_QUESTIONS);
            $this->questionsPage->draw($pdf, $pds, $this->options, $newPage);
        }
    }

    private function addTemplatePage(Fpdi $pdf, string $templatePage): void
    {
        $pdf->AddPage();
        $pdf->setSourceFile($this->layout->template($templatePage));

        $pageId = $pdf->importPage(1);

        if ($this->layout->templateWidth === null) {
            $pdf->useTemplate($pageId, $this->layout->templateX, $this->layout->templateY);

            return;
        }

        $pdf->useTemplate(
            $pageId,
            $this->layout->templateX,
            $this->layout->templateY,
            $this->layout->templateWidth,
        );
    }

    private function assertTemplatesExist(): void
    {
        $pages = [
            1 => PdsLayout::PAGE_PERSONAL,
            2 => PdsLayout::PAGE_CAREER,
            3 => PdsLayout::PAGE_LEARNING,
            4 => PdsLayout::PAGE_QUESTIONS,
        ];

        foreach ($pages as $page => $templatePage) {
            if (!$this->options->includes($page)) {
                continue;
            }

            if (!$this->layout->hasTemplate($templatePage)) {
                throw new RuntimeException('PDS template not found: ' . $this->layout->template($templatePage));
            }
        }
    }

    /**
     * @return list<Pds>
     */
    private function normalize(Pds|array $documents): array
    {
        return $documents instanceof Pds ? [$documents] : array_values($documents);
    }
}
