<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Feature;

use KawayanMedia\Pds\Adapter\ArrayPdsStore;
use KawayanMedia\Pds\Exception\ValidationException;
use KawayanMedia\Pds\Model\EducationLevel;
use KawayanMedia\Pds\Model\PdsProfile;
use KawayanMedia\Pds\Model\ServiceRecordEntry;
use KawayanMedia\Pds\Service\PdsEditor;
use KawayanMedia\Pds\Support\PdsSection;
use PHPUnit\Framework\TestCase;

final class PdsEditorTest extends TestCase
{
    private ArrayPdsStore $store;

    private PdsEditor $editor;

    protected function setUp(): void
    {
        $this->store = new ArrayPdsStore();
        $this->editor = new PdsEditor($this->store);
    }

    public function testSavePersonalInsertsThenUpdates(): void
    {
        $this->editor->savePersonal('1234', [
            'lname' => 'Dela Cruz',
            'fname' => 'Juan',
            'mname' => 'Santos',
            'birth_date' => '1985-03-14',
            'sex' => 'M',
            'res_address' => 'Boac, Marinduque',
            'unknown_column' => 'ignored',
        ]);

        $personal = $this->store->first(PdsSection::PERSONAL, '1234');

        self::assertNotNull($personal);
        self::assertSame('Dela Cruz', $personal['lname']);
        self::assertArrayNotHasKey('unknown_column', $personal);

        $this->editor->savePersonal('1234', ['lname' => 'Reyes', 'fname' => 'Juana']);

        self::assertCount(1, $this->store->find(PdsSection::PERSONAL, '1234'));
        self::assertSame('Reyes', $this->store->first(PdsSection::PERSONAL, '1234')['lname']);
        self::assertSame('Santos', $this->store->first(PdsSection::PERSONAL, '1234')['mname']);
    }

    public function testSavePersonalMirrorsTheEmployeeRecord(): void
    {
        $this->store->save(PdsSection::PROFILE, '1234', ['id' => '1234', 'position' => 'Clerk']);

        $this->editor->savePersonal('1234', [
            'lname' => 'Dela Cruz',
            'fname' => 'Juan',
            'birth_date' => '1985-03-14',
            'sex' => 'M',
            'res_address' => 'Boac, Marinduque',
        ]);

        $employee = $this->store->first(PdsSection::PROFILE, '1234');

        self::assertSame('1985-03-14', $employee['birth_date']);
        self::assertSame('M', $employee['sex']);
        self::assertSame('Boac, Marinduque', $employee['res_address']);
        self::assertSame('Clerk', $employee['position']);
    }

    public function testPersonalRequiresNames(): void
    {
        try {
            $this->editor->savePersonal('1234', ['lname' => '  ', 'fname' => '']);
            self::fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            self::assertSame([
                'lname' => 'The surname is required.',
                'fname' => 'The first name is required.',
            ], $exception->errors());
        }
    }

    public function testEmptyEmployeeIdIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->editor->saveTraining('', [['name' => 'Records Management']]);
    }

    public function testFamilyAndChildrenReplaceBlankRowsAway(): void
    {
        $this->editor->saveFamilyBackground('1234', ['spouse_lname' => 'Dela Cruz'], [
            ['children' => 'Ana', 'birth_date' => '2010-01-01'],
            ['children' => '', 'birth_date' => '2012-01-01'],
            ['children' => 'Ben', 'birth_date' => '2014-06-12'],
        ]);

        $children = $this->store->find(PdsSection::CHILDREN, '1234');

        self::assertCount(2, $children);
        self::assertSame('Ana', $children[0]['children']);
        self::assertSame('Ben', $children[1]['children']);

        $this->editor->saveFamilyBackground('1234', ['spouse_lname' => 'Reyes'], []);

        self::assertSame([], $this->store->find(PdsSection::CHILDREN, '1234'));
        self::assertSame('Reyes', $this->store->first(PdsSection::FAMILY, '1234')['spouse_lname']);
    }

    public function testEducationReplacesOnlyTheGivenLevels(): void
    {
        $this->editor->saveEducation('1234', [
            1 => ['school_name' => 'Boac North Central School', 'year_graduated' => '1997'],
            4 => ['school_name' => 'Marinduque State College', 'degree_course' => 'BSIT'],
        ]);

        $rows = $this->store->find(PdsSection::EDUCATION, '1234');

        self::assertCount(2, $rows);
        self::assertSame(1, $rows[0]['level']);
        self::assertSame(4, $rows[1]['level']);

        $this->editor->saveEducation('1234', [4 => ['school_name' => '']]);

        $rows = $this->store->find(PdsSection::EDUCATION, '1234');

        self::assertCount(1, $rows);
        self::assertSame(1, $rows[0]['level']);
    }

    public function testEducationAcceptsTheEnumAsLevel(): void
    {
        $this->editor->saveEducation('1234', [
            EducationLevel::College->value => ['school_name' => 'MSC'],
        ]);

        self::assertSame(4, $this->store->first(PdsSection::EDUCATION, '1234')['level']);
    }

    public function testUnknownEducationLevelIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->editor->saveEducation('1234', [9 => ['school_name' => 'Nowhere']]);
    }

    public function testEligibilityWorkVolunteerAndTrainingDropIncompleteRows(): void
    {
        $this->editor->saveEligibility('1234', [
            ['type' => 'Career Service Professional', 'rating' => '85.5'],
            ['type' => '', 'rating' => '80'],
        ]);

        $this->editor->saveWorkExperience('1234', [
            ['inclusive_date_from' => '2015-01-01', 'position' => 'Clerk'],
            ['inclusive_date_from' => '', 'position' => 'Ghost'],
        ]);

        $this->editor->saveVoluntaryWork('1234', [
            ['name' => 'Red Cross'],
            ['name' => ''],
        ]);

        $this->editor->saveTraining('1234', [
            ['name' => 'Records Management'],
            ['name' => '  '],
        ]);

        self::assertCount(1, $this->store->find(PdsSection::ELIGIBILITY, '1234'));
        self::assertCount(1, $this->store->find(PdsSection::WORK, '1234'));
        self::assertCount(1, $this->store->find(PdsSection::VOLUNTEER, '1234'));
        self::assertCount(1, $this->store->find(PdsSection::TRAINING, '1234'));
        self::assertSame('Clerk', $this->store->first(PdsSection::WORK, '1234')['position']);
    }

    public function testSaveReplacesPreviousRows(): void
    {
        $this->editor->saveTraining('1234', [['name' => 'First']]);
        $this->editor->saveTraining('1234', [['name' => 'Second'], ['name' => 'Third']]);

        $rows = $this->store->find(PdsSection::TRAINING, '1234');

        self::assertSame(['Second', 'Third'], array_column($rows, 'name'));
    }

    public function testOtherInfoNormalisesAnswersAndDropsBlankReferences(): void
    {
        $this->editor->saveOtherInfo(
            '1234',
            [
                ['special_skills' => 'Typing'],
                ['special_skills' => 'Programming', 'recognition' => 'Employee of the Year'],
            ],
            [
                ['question_no' => 1, 'answer' => 'yes', 'details' => ''],
                ['question_no' => 2, 'answer' => true],
                ['question_no' => 3, 'answer' => 'No'],
                ['question_no' => 4, 'answer' => 1, 'details' => 'Case 1234'],
            ],
            [
                ['name' => 'Maria Santos', 'address' => 'Boac', 'tel_no' => '1234'],
                ['name' => '', 'address' => '', 'tel_no' => ''],
            ],
        );

        $questions = $this->store->find(PdsSection::QUESTIONS, '1234');

        self::assertSame([1, 1, 0, 1], array_column($questions, 'answer'));
        self::assertSame([1, 2, 3, 4], array_column($questions, 'question_no'));
        self::assertSame('Case 1234', $questions[3]['details']);

        self::assertCount(2, $this->store->find(PdsSection::OTHER_INFO, '1234'));
        self::assertCount(1, $this->store->find(PdsSection::REFERENCES, '1234'));
    }

    public function testQuestionWithoutNumberIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->editor->saveOtherInfo('1234', [], [['answer' => 1]]);
    }

    public function testPositionDetailsAcceptTheTypedModel(): void
    {
        $this->store->save(PdsSection::PROFILE, '1234', ['id' => '1234']);

        $this->editor->savePositionDetails('1234', new PdsProfile(
            itemNumber: 'ITEM-1',
            position: 'Information Technology Officer I',
            officeId: 7,
            salaryGrade: 19,
            step: 2,
        ));

        $employee = $this->store->first(PdsSection::PROFILE, '1234');

        self::assertSame('ITEM-1', $employee['item_number']);
        self::assertSame('Information Technology Officer I', $employee['position']);
        self::assertSame(19, $employee['salary_grade']);
    }

    public function testPositionDetailsNeverCreateAnEmployee(): void
    {
        $this->editor->savePositionDetails('9999', ['position' => 'Ghost']);

        self::assertSame([], $this->store->find(PdsSection::PROFILE, '9999'));
    }

    public function testServiceRecordAcceptsModelsAndArrays(): void
    {
        $this->editor->saveServiceRecord('1234', [
            new ServiceRecordEntry(dateFrom: '2015-01-01', designation: 'Clerk', status: 'Permanent'),
            ['date_from' => '2018-03-01', 'designation' => 'Officer'],
            ['date_from' => ''],
        ]);

        $rows = $this->store->find(PdsSection::SERVICE_RECORD, '1234');

        self::assertCount(2, $rows);
        self::assertSame('2015-01-01', $rows[0]['date_from']);
        self::assertSame('Officer', $rows[1]['designation']);
    }

    public function testRowsMustBeArrays(): void
    {
        $this->expectException(ValidationException::class);

        $this->editor->saveTraining('1234', ['Records Management']);
    }

    public function testLoadReadsTheSectionBack(): void
    {
        $this->editor->saveTraining('1234', [['name' => 'Records Management']]);

        self::assertSame('Records Management', $this->editor->load(PdsSection::TRAINING, '1234')[0]['name']);
    }

    public function testAnswersAreNormalised(): void
    {
        self::assertSame(1, PdsEditor::answer('y'));
        self::assertSame(1, PdsEditor::answer('Yes'));
        self::assertSame(0, PdsEditor::answer('false'));
        self::assertSame(0, PdsEditor::answer(0));
        self::assertSame(1, PdsEditor::answer(2));
        self::assertSame(1, PdsEditor::answer(true));
        self::assertSame(0, PdsEditor::answer(null));
    }
}
