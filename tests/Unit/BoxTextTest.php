<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Support\BoxText;
use PHPUnit\Framework\TestCase;

final class BoxTextTest extends TestCase
{
    public function test_it_pads_short_values_to_the_box_count(): void
    {
        $boxes = BoxText::boxes('Dela Cruz');

        self::assertCount(30, $boxes);
        self::assertSame(['D', 'e', 'l', 'a', ' ', 'C', 'r', 'u', 'z'], array_slice($boxes, 0, 9));
        self::assertSame('', $boxes[29]);
    }

    public function test_it_drops_characters_past_the_last_box(): void
    {
        $boxes = BoxText::boxes(str_repeat('A', 40), 10);

        self::assertCount(10, $boxes);
        self::assertSame(str_split(str_repeat('A', 10)), $boxes);
    }

    public function test_an_empty_value_produces_empty_boxes(): void
    {
        self::assertSame(array_fill(0, 30, ''), BoxText::boxes(''));
    }

    public function test_accented_characters_take_one_box(): void
    {
        $boxes = BoxText::boxes('Muñoz');

        // One box per ISO-8859-1 byte, which is what FPDF prints.
        self::assertSame(['M', 'u', chr(241), 'o', 'z'], array_slice($boxes, 0, 5));
    }
}
