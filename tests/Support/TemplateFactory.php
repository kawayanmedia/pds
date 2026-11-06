<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Support;

use FPDF;

/**
 * Builds four blank templates so the feature tests never depend on the bundled
 * (scanned) CS Form 212 files.
 */
final class TemplateFactory
{
    /**
     * @return array<string, string> page key => path
     */
    public static function create(string $directory): array
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $templates = [];

        foreach (['page1', 'page2', 'page3', 'page4'] as $page) {
            $path = $directory . '/' . $page . '.pdf';
            self::blank($path, strtoupper($page) . ' TEMPLATE');
            $templates[$page] = $path;
        }

        return $templates;
    }

    public static function blank(string $path, string $label): void
    {
        $pdf = new FPDF('P', 'mm', 'Legal');
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(10, 5);
        $pdf->Cell(0, 5, $label);
        $pdf->Output('F', $path);
    }

    public static function pageCount(string $pdf): int
    {
        preg_match_all('/\/Type\s*\/Page\b/', $pdf, $matches);

        return count($matches[0]);
    }
}
