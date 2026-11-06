<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

/**
 * The five pre-printed rows of the educational background section.
 */
enum EducationLevel: int
{
    case Elementary = 1;

    case Secondary = 2;

    case Vocational = 3;

    case College = 4;

    case GraduateStudies = 5;

    public static function fromValue(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        return is_numeric($value) ? self::tryFrom((int) $value) : null;
    }

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Secondary => 'Secondary',
            self::Vocational => 'Vocational / Trade Course',
            self::College => 'College',
            self::GraduateStudies => 'Graduate Studies',
        };
    }
}
