<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Config;

use KawayanMedia\Pds\Support\Dates;

/**
 * One printed value: where it goes and how it is written.
 *
 * Every SetXY()/Write() pair of the legacy Pds_preview controller becomes one
 * placement, so the page painters contain no coordinates of their own.
 */
final class Placement
{
    /** Plain single-line text. */
    public const TEXT = 'text';

    /** One character per pre-printed box (the name fields). */
    public const BOXED = 'boxed';

    /** A formatted date. */
    public const DATE = 'date';

    /** Text split over two pre-printed lines. */
    public const TWO_LINE = 'two-line';

    /** An "X" when the value matches $equals. */
    public const CHECK = 'check';

    /**
     * @param list<float> $boxX
     */
    public function __construct(
        public readonly string $key,
        public readonly float $x,
        public readonly float $y,
        public readonly string $mode = self::TEXT,
        public readonly ?float $fontSize = null,
        public readonly int $maxChars = 0,
        public readonly bool $titleCase = false,
        public readonly string $format = Dates::SLASH_DATE,
        public readonly ?string $equals = null,
        public readonly array $boxX = [],
        public readonly float $secondLineOffset = 0.0,
        public readonly int $wrapWidth = 30,
    ) {
    }

    public static function text(
        string $key,
        float $x,
        float $y,
        ?float $fontSize = null,
        int $maxChars = 0,
        bool $titleCase = false,
    ): self {
        return new self(
            key: $key,
            x: $x,
            y: $y,
            mode: self::TEXT,
            fontSize: $fontSize,
            maxChars: $maxChars,
            titleCase: $titleCase,
        );
    }

    /**
     * @param list<float> $boxX
     */
    public static function boxed(string $key, array $boxX, float $y, ?float $fontSize = null): self
    {
        return new self(
            key: $key,
            x: $boxX[0] ?? 0.0,
            y: $y,
            mode: self::BOXED,
            fontSize: $fontSize,
            boxX: array_values($boxX),
        );
    }

    public static function date(
        string $key,
        float $x,
        float $y,
        string $format = Dates::SLASH_DATE,
        ?float $fontSize = null,
    ): self {
        return new self(
            key: $key,
            x: $x,
            y: $y,
            mode: self::DATE,
            fontSize: $fontSize,
            format: $format,
        );
    }

    public static function twoLine(
        string $key,
        float $x,
        float $y,
        float $secondLineOffset,
        int $wrapWidth = 30,
        ?float $fontSize = null,
    ): self {
        return new self(
            key: $key,
            x: $x,
            y: $y,
            mode: self::TWO_LINE,
            fontSize: $fontSize,
            secondLineOffset: $secondLineOffset,
            wrapWidth: $wrapWidth,
        );
    }

    public static function check(string $key, string $equals, float $x, float $y, ?float $fontSize = null): self
    {
        return new self(
            key: $key,
            x: $x,
            y: $y,
            mode: self::CHECK,
            fontSize: $fontSize,
            equals: $equals,
        );
    }
}
