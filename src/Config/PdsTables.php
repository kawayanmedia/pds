<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Config;

use KawayanMedia\Pds\Support\PdsSection;

/**
 * Maps every write section to the table and key column it lives in, so a
 * deployment can keep its own naming.
 */
final class PdsTables
{
    /**
     * @var array<string, string>
     */
    private const TABLES = [
        PdsSection::PERSONAL => 'pds_personal_info',
        PdsSection::FAMILY => 'pds_family_background',
        PdsSection::CHILDREN => 'pds_children',
        PdsSection::EDUCATION => 'pds_education_background',
        PdsSection::ELIGIBILITY => 'pds_eligibility',
        PdsSection::WORK => 'pds_work_experience',
        PdsSection::VOLUNTEER => 'pds_organization',
        PdsSection::TRAINING => 'pds_training',
        PdsSection::OTHER_INFO => 'pds_other_info',
        PdsSection::QUESTIONS => 'pds_questions',
        PdsSection::REFERENCES => 'pds_references',
        PdsSection::SERVICE_RECORD => 'service_record',
        PdsSection::PROFILE => 'employee',
    ];

    /**
     * @var array<string, string>
     */
    private const KEYS = [
        PdsSection::PROFILE => 'id',
    ];

    /**
     * @param array<string, string> $tables section => table overrides
     * @param array<string, string> $keys   section => key column overrides
     */
    public function __construct(
        private readonly array $tables = [],
        private readonly array $keys = [],
    ) {
    }

    public static function legacy(): self
    {
        return new self();
    }

    /**
     * @param array<string, string> $tables
     * @param array<string, string> $keys
     */
    public static function fromArray(array $tables, array $keys = []): self
    {
        return new self($tables, $keys);
    }

    public function table(string $section): string
    {
        PdsSection::assert($section);

        return $this->tables[$section] ?? self::TABLES[$section];
    }

    public function key(string $section): string
    {
        PdsSection::assert($section);

        return $this->keys[$section] ?? self::KEYS[$section] ?? 'employee_id';
    }

    /**
     * @return array<string, string> section => table
     */
    public function toArray(): array
    {
        return array_merge(self::TABLES, $this->tables);
    }
}
