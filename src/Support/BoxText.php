<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Support;

/**
 * The CS Form 212 name fields are printed one character per pre-printed box.
 * A box count of 30 covers the widest name the form allows.
 */
final class BoxText
{
    public const NAME_BOXES = 30;

    /**
     * Splits a value into exactly $count boxes, padding with blanks and
     * dropping anything past the last box.
     *
     * The legacy controller indexed the result of str_split() directly, which
     * raised "undefined array key" warnings for every short name.
     *
     * @return list<string>
     */
    public static function boxes(string $value, int $count = self::NAME_BOXES): array
    {
        $characters = $value === '' ? [] : str_split(Text::latin1($value));

        $boxes = [];

        for ($index = 0; $index < $count; $index++) {
            $boxes[] = $characters[$index] ?? '';
        }

        return $boxes;
    }
}
