<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Support;

/**
 * Line splitting used by the education table and the two-line address fields.
 */
final class WordChunks
{
    /**
     * Legacy education cells: wordwrap(strtoupper/lower text, 23, '|') exploded
     * into at most four printed lines. Short values are padded so callers can
     * always address every line.
     *
     * @return list<string>
     */
    public static function lines(string $value, int $width, int $lines = 4): array
    {
        $value = trim((string) preg_replace('/\s+/', ' ', Text::latin1($value)));
        $chunks = $value === '' ? [] : explode('|', (string) wordwrap($value, $width, '|', true));
        $result = [];

        for ($index = 0; $index < $lines; $index++) {
            $result[] = $chunks[$index] ?? '';
        }

        return $result;
    }

    /**
     * Replacement for the legacy splitstroverflow() helper.
     *
     * The legacy version moved a word to the second line as soon as the running
     * length reached the limit, and put a single over-long word entirely on the
     * second line. This version keeps as many words as fit on the first line and
     * never leaves the first line empty, which is what the pre-printed address
     * boxes expect.
     *
     * @return array{0: string, 1: string}
     */
    public static function splitOverflow(string $value, int $limit = 30): array
    {
        $value = trim((string) preg_replace('/\s+/', ' ', Text::latin1($value)));

        if ($value === '') {
            return ['', ''];
        }

        $first = '';
        $second = '';

        foreach (explode(' ', $value) as $word) {
            $candidate = $first === '' ? $word : $first . ' ' . $word;

            if ($first === '' || strlen($candidate) <= $limit) {
                $first = $candidate;

                continue;
            }

            $second = $second === '' ? $word : $second . ' ' . $word;
        }

        return [$first, $second];
    }
}
