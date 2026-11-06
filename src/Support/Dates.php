<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Support;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * The four date formats the CS Form 212 prints.
 */
final class Dates
{
    public const MONTH_DAY_YEAR = 'm-d-Y';

    public const SLASH_DATE = 'm/d/Y';

    public const LONG_DATE = 'F d, Y';

    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || $value === '0000-00-00' || strcasecmp($value, 'present') === 0) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function format(mixed $value, string $format): string
    {
        $date = self::parse($value);

        return $date === null ? '' : $date->format($format);
    }

    /**
     * Legacy birth date column: $month.'-'.$day.'-'.$year.
     */
    public static function monthDayYear(mixed $value): string
    {
        return self::format($value, self::MONTH_DAY_YEAR);
    }

    /**
     * Legacy children/work/reference columns: $month.'/'.$day.'/'.$year.
     */
    public static function slashDate(mixed $value): string
    {
        return self::format($value, self::SLASH_DATE);
    }

    public static function longDate(mixed $value): string
    {
        return self::format($value, self::LONG_DATE);
    }
}
