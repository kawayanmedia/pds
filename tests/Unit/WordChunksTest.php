<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Support\WordChunks;
use PHPUnit\Framework\TestCase;

final class WordChunksTest extends TestCase
{
    public function test_lines_are_wrapped_at_the_requested_width(): void
    {
        $lines = WordChunks::lines('Marinduque National High School', 20);

        self::assertSame('Marinduque National', $lines[0]);
        self::assertSame('High School', $lines[1]);
    }

    public function test_short_values_are_padded_to_four_lines(): void
    {
        $lines = WordChunks::lines('Boac Central', 23);

        self::assertCount(4, $lines);
        self::assertSame('Boac Central', $lines[0]);
        self::assertSame(['', '', ''], array_slice($lines, 1));
    }

    public function test_split_overflow_keeps_words_whole(): void
    {
        [$first, $second] = WordChunks::splitOverflow('123 Sample Street, Barangay Malusak, Boac', 30);

        self::assertSame('123 Sample Street, Barangay', $first);
        self::assertSame('Malusak, Boac', $second);
    }

    public function test_split_overflow_keeps_a_long_word_on_the_first_line(): void
    {
        [$first, $second] = WordChunks::splitOverflow('Supercalifragilisticexpialidocious Street', 10);

        self::assertSame('Supercalifragilisticexpialidocious', $first);
        self::assertSame('Street', $second);
    }

    public function test_split_overflow_of_an_empty_value_is_empty(): void
    {
        self::assertSame(['', ''], WordChunks::splitOverflow(''));
    }
}
