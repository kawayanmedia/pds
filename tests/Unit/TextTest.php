<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Support\Text;
use PHPUnit\Framework\TestCase;

final class TextTest extends TestCase
{
    public function test_title_case_matches_the_legacy_casing(): void
    {
        self::assertSame('Marinduque National High School', Text::titleCase('MARINDUQUE NATIONAL HIGH SCHOOL'));
    }

    public function test_limit_returns_short_values_untouched(): void
    {
        self::assertSame('Computer Operator', Text::limit('Computer Operator', 20));
    }

    public function test_limit_truncates_on_a_word_boundary(): void
    {
        $value = Text::limitForPdf('Information Technology Officer I', 20);

        self::assertSame('Information Technology..', $value);
    }

    public function test_blank_normalises_null_and_whitespace(): void
    {
        self::assertSame('', Text::blank(null));
        self::assertSame('', Text::blank('   '));
        self::assertSame('Yes', Text::blank(' Yes '));
    }
}
