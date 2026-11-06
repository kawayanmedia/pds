<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Model\EducationLevel;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Model\PersonalInfo;
use KawayanMedia\Pds\Model\WorkExperience;
use KawayanMedia\Pds\Tests\Support\PdsFixture;
use PHPUnit\Framework\TestCase;

final class PdsModelTest extends TestCase
{
    public function test_it_maps_every_legacy_section(): void
    {
        $pds = Pds::fromArray(PdsFixture::rows());

        self::assertSame('013001', $pds->employeeId);
        self::assertSame('Dela Cruz', $pds->personal->lastName);
        self::assertSame('Dela Cruz', $pds->family?->spouseLastName);
        self::assertCount(2, $pds->children);
        self::assertCount(3, $pds->education);
        self::assertCount(1, $pds->eligibility);
        self::assertCount(2, $pds->work);
        self::assertCount(1, $pds->volunteer);
        self::assertCount(2, $pds->training);
        self::assertCount(1, $pds->otherInfo);
        self::assertCount(1, $pds->references);
    }

    public function test_children_are_ordered_by_birth_date(): void
    {
        $rows = PdsFixture::rows();
        $rows['children'] = array_reverse($rows['children']);

        $pds = Pds::fromArray($rows);

        self::assertSame('Jose Dela Cruz', $pds->children[0]->name);
    }

    public function test_work_is_ordered_from_the_most_recent(): void
    {
        $pds = Pds::fromArray(PdsFixture::rows());

        self::assertSame('2010-01-04', $pds->work[0]->dateFrom);
        self::assertSame(WorkExperience::PRESENT, $pds->work[0]->dateToLabel());
        self::assertSame('12/31/2009', $pds->work[1]->dateToLabel());
    }

    public function test_training_is_ordered_from_the_most_recent(): void
    {
        $pds = Pds::fromArray(PdsFixture::rows());

        self::assertSame('Records Management and Digitisation Workshop', $pds->training[0]->name);
    }

    public function test_education_can_be_selected_by_level(): void
    {
        $pds = Pds::fromArray(PdsFixture::rows());

        self::assertCount(1, $pds->educationFor(EducationLevel::College));
        self::assertSame([], $pds->educationFor(EducationLevel::GraduateStudies));
    }

    public function test_answers_accept_question_rows_and_a_map(): void
    {
        $rows = PdsFixture::rows();
        $rows['answers'] = [3 => true];

        $pds = Pds::fromArray($rows);

        self::assertFalse($pds->answer(1));
        self::assertTrue($pds->answer(3));
        self::assertNull($pds->answer(11));
    }

    public function test_missing_sections_are_safe(): void
    {
        $pds = Pds::fromArray(['employee_id' => '1']);

        self::assertSame('', $pds->personal->lastName);
        self::assertNull($pds->family);
        self::assertNull($pds->firstReference());
        self::assertSame([], $pds->children);
    }

    public function test_personal_info_exposes_the_legacy_column_names(): void
    {
        $values = PersonalInfo::fromArray(['lname' => 'Cruz', 'sex' => 'f'])->toValues();

        self::assertSame('Cruz', $values['lname']);
        self::assertSame('F', $values['sex']);
        self::assertArrayHasKey('agency_employee_no', $values);
    }

    public function test_full_name_adds_the_extension(): void
    {
        $info = PersonalInfo::fromArray([
            'fname' => 'Juan',
            'mname' => 'Santos',
            'lname' => 'Dela Cruz',
            'extension' => 'Jr',
        ]);

        self::assertSame('Juan Santos Dela Cruz Jr', $info->fullName());
    }
}
