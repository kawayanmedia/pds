<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Support;

use KawayanMedia\Pds\Exception\ValidationException;

/**
 * The vocabulary the write side speaks. Every section names one legacy table
 * (see {@see \KawayanMedia\Pds\Config\PdsTables}) and one editor method.
 */
final class PdsSection
{
    public const PERSONAL = 'personal';
    public const FAMILY = 'family';
    public const CHILDREN = 'children';
    public const EDUCATION = 'education';
    public const ELIGIBILITY = 'eligibility';
    public const WORK = 'work';
    public const VOLUNTEER = 'volunteer';
    public const TRAINING = 'training';
    public const OTHER_INFO = 'other_info';
    public const QUESTIONS = 'questions';
    public const REFERENCES = 'references';
    public const SERVICE_RECORD = 'service_record';
    public const PROFILE = 'profile';

    /**
     * Sections that hold exactly one row per employee.
     *
     * @var list<string>
     */
    private const SINGLETONS = [self::PERSONAL, self::FAMILY, self::PROFILE];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PERSONAL,
            self::FAMILY,
            self::CHILDREN,
            self::EDUCATION,
            self::ELIGIBILITY,
            self::WORK,
            self::VOLUNTEER,
            self::TRAINING,
            self::OTHER_INFO,
            self::QUESTIONS,
            self::REFERENCES,
            self::SERVICE_RECORD,
            self::PROFILE,
        ];
    }

    public static function assert(string $section): string
    {
        if (!in_array($section, self::all(), true)) {
            throw ValidationException::forField('section', sprintf('Unknown PDS section "%s".', $section));
        }

        return $section;
    }

    public static function isSingleton(string $section): bool
    {
        return in_array($section, self::SINGLETONS, true);
    }
}
