<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use DateTimeImmutable;
use KawayanMedia\Pds\Support\Dates;
use PHPUnit\Framework\TestCase;

final class DatesTest extends TestCase
{
    public function test_it_formats_the_three_legacy_shapes(): void
    {
        self::assertSame('03-14-1985', Dates::monthDayYear('1985-03-14'));
        self::assertSame('03/14/1985', Dates::slashDate('1985-03-14 00:00:00'));
        self::assertSame('March 14, 1985', Dates::longDate('1985-03-14'));
    }

    public function test_empty_and_present_values_print_blank(): void
    {
        self::assertSame('', Dates::slashDate(''));
        self::assertSame('', Dates::slashDate(null));
        self::assertSame('', Dates::slashDate('0000-00-00'));
        self::assertSame('', Dates::slashDate('Present'));
    }

    public function test_it_accepts_date_objects(): void
    {
        self::assertSame('11/06/2026', Dates::slashDate(new DateTimeImmutable('2026-11-06')));
    }
}
