<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Config\PdsTables;
use KawayanMedia\Pds\Exception\ValidationException;
use KawayanMedia\Pds\Support\PdsSection;
use PHPUnit\Framework\TestCase;

final class PdsTablesTest extends TestCase
{
    public function testLegacyDefaults(): void
    {
        $tables = PdsTables::legacy();

        self::assertSame('pds_personal_info', $tables->table(PdsSection::PERSONAL));
        self::assertSame('pds_family_background', $tables->table(PdsSection::FAMILY));
        self::assertSame('pds_children', $tables->table(PdsSection::CHILDREN));
        self::assertSame('pds_education_background', $tables->table(PdsSection::EDUCATION));
        self::assertSame('pds_eligibility', $tables->table(PdsSection::ELIGIBILITY));
        self::assertSame('pds_work_experience', $tables->table(PdsSection::WORK));
        self::assertSame('pds_organization', $tables->table(PdsSection::VOLUNTEER));
        self::assertSame('pds_training', $tables->table(PdsSection::TRAINING));
        self::assertSame('pds_other_info', $tables->table(PdsSection::OTHER_INFO));
        self::assertSame('pds_questions', $tables->table(PdsSection::QUESTIONS));
        self::assertSame('pds_references', $tables->table(PdsSection::REFERENCES));
        self::assertSame('service_record', $tables->table(PdsSection::SERVICE_RECORD));
        self::assertSame('employee', $tables->table(PdsSection::PROFILE));
    }

    public function testKeyColumns(): void
    {
        $tables = PdsTables::legacy();

        self::assertSame('employee_id', $tables->key(PdsSection::PERSONAL));
        self::assertSame('id', $tables->key(PdsSection::PROFILE));
    }

    public function testOverrides(): void
    {
        $tables = PdsTables::fromArray(
            [PdsSection::PERSONAL => 'hr_personal', PdsSection::PROFILE => 'hr_employee'],
            [PdsSection::PROFILE => 'employee_no'],
        );

        self::assertSame('hr_personal', $tables->table(PdsSection::PERSONAL));
        self::assertSame('hr_employee', $tables->table(PdsSection::PROFILE));
        self::assertSame('employee_no', $tables->key(PdsSection::PROFILE));
        self::assertSame('pds_children', $tables->table(PdsSection::CHILDREN));
    }

    public function testUnknownSectionIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        PdsTables::legacy()->table('hobbies');
    }

    public function testSingletonSections(): void
    {
        self::assertTrue(PdsSection::isSingleton(PdsSection::PERSONAL));
        self::assertTrue(PdsSection::isSingleton(PdsSection::FAMILY));
        self::assertTrue(PdsSection::isSingleton(PdsSection::PROFILE));
        self::assertFalse(PdsSection::isSingleton(PdsSection::CHILDREN));
        self::assertCount(13, PdsSection::all());
    }
}
