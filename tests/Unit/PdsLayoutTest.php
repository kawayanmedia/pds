<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\Placement;
use PHPUnit\Framework\TestCase;

final class PdsLayoutTest extends TestCase
{
    public function test_the_bundled_templates_ship_with_the_package(): void
    {
        $layout = PdsLayout::legacyIhrmis();

        foreach (['page1', 'page2', 'page3', 'page4'] as $page) {
            self::assertTrue($layout->hasTemplate($page), $page . ' template is missing');
        }
    }

    public function test_templates_can_be_pointed_at_a_directory(): void
    {
        $layout = PdsLayout::fromDirectory('/tmp/pds-templates');

        self::assertSame('/tmp/pds-templates/page1.pdf', $layout->template(PdsLayout::PAGE_PERSONAL));
        self::assertSame('/tmp/pds-templates/page4.pdf', $layout->template(PdsLayout::PAGE_QUESTIONS));
    }

    public function test_the_personal_page_places_every_field_once(): void
    {
        $placements = PdsLayout::personalPlacements();
        $keys = array_map(static fn (Placement $placement): string => $placement->key, $placements);

        foreach (['lname', 'fname', 'mname', 'extension', 'birth_date', 'birth_place', 'citizenship',
            'gsis', 'pagibig', 'philhealth', 'sss', 'res_zip', 'res_tel', 'tin',
            'spouse_lname', 'father_lname', 'mother_mname'] as $key) {
            self::assertContains($key, $keys, $key . ' is not placed');
        }

        // Six civil status boxes plus the two sex boxes.
        self::assertCount(8, array_filter(
            $placements,
            static fn (Placement $placement): bool => $placement->mode === Placement::CHECK,
        ));
    }

    public function test_name_rows_share_the_thirty_box_grid(): void
    {
        $boxed = array_values(array_filter(
            PdsLayout::personalPlacements(),
            static fn (Placement $placement): bool => $placement->mode === Placement::BOXED,
        ));

        self::assertCount(3, $boxed);
        self::assertSame([53.0, 59.0, 65.0], array_map(
            static fn (Placement $placement): float => $placement->y,
            $boxed,
        ));
        self::assertCount(count(PdsLayout::NAME_BOX_X), $boxed[0]->boxX);
    }

    public function test_the_legacy_row_pitch_is_used_by_the_tables(): void
    {
        $layout = PdsLayout::legacyIhrmis();

        self::assertSame(7.35, $layout->eligibility->rowHeight);
        self::assertSame(7.35, $layout->work->rowHeight);
        self::assertSame(7.35, $layout->training->rowHeight);
    }

    public function test_templates_can_be_replaced(): void
    {
        $layout = PdsLayout::legacyIhrmis()->withTemplates(['page1' => '/tmp/one.pdf']);

        self::assertSame('/tmp/one.pdf', $layout->template(PdsLayout::PAGE_PERSONAL));
        self::assertFalse($layout->hasTemplate(PdsLayout::PAGE_CAREER));
    }
}
