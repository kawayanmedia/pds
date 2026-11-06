<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Feature;

use KawayanMedia\Pds\Adapter\PdoPdsStore;
use KawayanMedia\Pds\Config\PdsTables;
use KawayanMedia\Pds\Model\PdsProfile;
use KawayanMedia\Pds\Service\PdsEditor;
use KawayanMedia\Pds\Support\PdsSection;
use KawayanMedia\Persistence\Adapter\PdoDatabase;
use KawayanMedia\Persistence\Contract\DatabaseInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdoPdsStoreTest extends TestCase
{
    private PdoDatabase $database;

    private PdoPdsStore $store;

    private PdsEditor $editor;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('The pdo_sqlite extension is not installed.');
        }

        $this->database = PdoDatabase::fromDsn('sqlite::memory:');
        $this->store = new PdoPdsStore($this->database);
        $this->editor = new PdsEditor($this->store);

        $this->createSchema();
    }

    public function testPersonalIsInsertedThenUpdated(): void
    {
        $this->editor->savePersonal('1234', [
            'lname' => 'Dela Cruz',
            'fname' => 'Juan',
            'mname' => 'Santos',
            'birth_date' => '1985-03-14',
            'sex' => 'M',
            'res_address' => 'Boac, Marinduque',
        ]);

        self::assertSame(1, $this->database->count('pds_personal_info'));
        self::assertSame('Dela Cruz', $this->store->first(PdsSection::PERSONAL, '1234')['lname']);

        $this->editor->savePersonal('1234', ['lname' => 'Reyes', 'fname' => 'Juana']);

        self::assertSame(1, $this->database->count('pds_personal_info'));
        self::assertSame('Reyes', $this->store->first(PdsSection::PERSONAL, '1234')['lname']);
        self::assertSame('Santos', $this->store->first(PdsSection::PERSONAL, '1234')['mname']);
    }

    public function testPersonalMirrorsTheEmployeeRow(): void
    {
        $this->database->insert('employee', ['id' => '1234', 'position' => 'Clerk']);

        $this->editor->savePersonal('1234', [
            'lname' => 'Dela Cruz',
            'fname' => 'Juan',
            'birth_date' => '1985-03-14',
            'sex' => 'M',
            'res_address' => 'Boac, Marinduque',
        ]);

        $employee = $this->database->select('employee', ['*'], ['id' => '1234'])[0];

        self::assertSame('1985-03-14', $employee['birth_date']);
        self::assertSame('M', $employee['sex']);
        self::assertSame('Boac, Marinduque', $employee['res_address']);
        self::assertSame('Clerk', $employee['position']);
    }

    public function testChildrenAreReplaced(): void
    {
        $this->editor->saveFamilyBackground('1234', ['spouse_lname' => 'Dela Cruz'], [
            ['children' => 'Ana', 'birth_date' => '2010-01-01'],
            ['children' => '', 'birth_date' => '2012-01-01'],
        ]);

        self::assertSame(1, $this->database->count('pds_children'));

        $this->editor->saveFamilyBackground('1234', ['spouse_lname' => 'Dela Cruz'], [
            ['children' => 'Ben', 'birth_date' => '2014-06-12'],
            ['children' => 'Cara', 'birth_date' => '2016-06-12'],
        ]);

        $rows = $this->store->find(PdsSection::CHILDREN, '1234');

        self::assertSame(['Ben', 'Cara'], array_column($rows, 'children'));
    }

    public function testEducationOnlyTouchesTheGivenLevel(): void
    {
        $this->editor->saveEducation('1234', [
            1 => ['school_name' => 'Boac North Central School'],
            4 => ['school_name' => 'Marinduque State College'],
        ]);

        self::assertSame(2, $this->database->count('pds_education_background'));

        $this->editor->saveEducation('1234', [4 => ['school_name' => '']]);

        $rows = $this->store->find(PdsSection::EDUCATION, '1234');

        self::assertCount(1, $rows);
        self::assertSame(1, (int) $rows[0]['level']);

        $this->editor->saveEducation('1234', [1 => ['school_name' => 'Boac Central School']]);

        $rows = $this->store->find(PdsSection::EDUCATION, '1234');

        self::assertCount(1, $rows);
        self::assertSame('Boac Central School', $rows[0]['school_name']);
    }

    public function testOtherSectionsAreReplaced(): void
    {
        $this->editor->saveEligibility('1234', [['type' => 'Career Service Professional']]);
        $this->editor->saveEligibility('1234', [
            ['type' => 'Career Service Professional'],
            ['type' => 'RA 1080'],
        ]);
        $this->editor->saveWorkExperience('1234', [['inclusive_date_from' => '2015-01-01', 'position' => 'Clerk']]);
        $this->editor->saveVoluntaryWork('1234', [['name' => 'Red Cross']]);
        $this->editor->saveTraining('1234', [['name' => 'Records Management']]);
        $this->editor->saveServiceRecord('1234', [['date_from' => '2015-01-01', 'designation' => 'Clerk']]);

        self::assertSame(2, $this->database->count('pds_eligibility'));
        self::assertSame(1, $this->database->count('pds_work_experience'));
        self::assertSame(1, $this->database->count('pds_organization'));
        self::assertSame(1, $this->database->count('pds_training'));
        self::assertSame(1, $this->database->count('service_record'));
    }

    public function testOtherInformationQuestionsAndReferences(): void
    {
        $this->editor->saveOtherInfo(
            '1234',
            [['special_skills' => 'Typing']],
            [
                ['question_no' => 1, 'answer' => 'yes'],
                ['question_no' => 2, 'answer' => 'no', 'details' => 'Nothing to declare'],
            ],
            [['name' => 'Maria Santos', 'address' => 'Boac', 'tel_no' => '1234']],
        );

        $questions = $this->store->find(PdsSection::QUESTIONS, '1234');

        self::assertSame([1, 0], array_map('intval', array_column($questions, 'answer')));
        self::assertSame('Nothing to declare', $questions[1]['details']);
        self::assertSame(1, $this->database->count('pds_references'));
    }

    public function testPositionDetailsUpdateTheEmployeeRow(): void
    {
        $this->database->insert('employee', ['id' => '1234']);

        $this->editor->savePositionDetails('1234', new PdsProfile(itemNumber: 'ITEM-1', position: 'ITO I'));

        $employee = $this->database->select('employee', ['*'], ['id' => '1234'])[0];

        self::assertSame('ITEM-1', $employee['item_number']);
        self::assertSame('ITO I', $employee['position']);
        self::assertSame(1, $this->database->count('employee'));
    }

    public function testCustomTableNames(): void
    {
        $this->database->execute('create table hr_personal (id text primary key, lname text, fname text)');

        $store = new PdoPdsStore($this->database, PdsTables::fromArray(
            [PdsSection::PERSONAL => 'hr_personal'],
            [PdsSection::PERSONAL => 'id'],
        ));

        (new PdsEditor($store))->savePersonal('1234', ['lname' => 'Dela Cruz', 'fname' => 'Juan']);

        self::assertSame('Dela Cruz', $this->database->select('hr_personal', ['lname'], ['id' => '1234'])[0]['lname']);
    }

    public function testStoreTransactionRollsBackEverySection(): void
    {
        try {
            $this->store->transaction(function (PdoPdsStore $store) use (&$editor): void {
                $store2 = $store;
                (new PdsEditor($store2))->saveTraining('1234', [['name' => 'Records Management']]);
                (new PdsEditor($store2))->saveVoluntaryWork('1234', [['name' => 'Red Cross']]);

                throw new RuntimeException('boom');
            });

            self::fail('The exception should have been rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(0, $this->database->count('pds_training'));
        self::assertSame(0, $this->database->count('pds_organization'));
    }

    public function testUnknownSectionIsRejected(): void
    {
        $this->expectException(\KawayanMedia\Pds\Exception\ValidationException::class);

        $this->store->find('hobbies', '1234');
    }

    public function testDatabaseIsExposed(): void
    {
        self::assertInstanceOf(DatabaseInterface::class, $this->database);
        self::assertSame('employee', $this->store->tables()->table(PdsSection::PROFILE));
    }

    private function createSchema(): void
    {
        $text = 'text';

        $this->database->execute('create table employee (id text primary key)');
        $this->database->execute('create table pds_personal_info (employee_id text primary key, lname text, fname text, mname text, extension text, birth_date text, birth_place text, sex text, civil_status text, citizenship text, height text, weight text, blood_type text, gsis text, pagibig text, philhealth text, sss text, res_address text, res_zip text, res_tel text, permanent_address text, permanent_zip text, permanent_tel text, email text, cp text, agency_employee_no text, tin text)');
        $this->database->execute('create table pds_family_background (employee_id text primary key, spouse_lname text, spouse_fname text, spouse_mname text, spouse_occupation text, spouse_employer text, spouse_biz_ad text, spouse_tel text, father_lname text, father_fname text, father_mname text, mother_lname text, mother_fname text, mother_mname text)');
        $this->database->execute('create table pds_children (id integer primary key autoincrement, employee_id text, children text, birth_date text)');
        $this->database->execute('create table pds_education_background (id integer primary key autoincrement, employee_id text, level integer, school_name text, degree_course text, year_graduated text, highest_grade text, attend_from text, attend_to text, scholarship text)');
        $this->database->execute('create table pds_eligibility (id integer primary key autoincrement, employee_id text, type text, rating text, date_exam_conferment text, place_exam_conferment text, license_no text, license_release_date text)');
        $this->database->execute('create table pds_work_experience (id integer primary key autoincrement, employee_id text, inclusive_date_from text, inclusive_date_to text, position text, company text, monthly_salary text, salary_grade text, status text, movement text, govt_service text)');
        $this->database->execute('create table pds_organization (id integer primary key autoincrement, employee_id text, name text, inclusive_date_from text, inclusive_date_to text, number_of_hours text, position text)');
        $this->database->execute('create table pds_training (id integer primary key autoincrement, employee_id text, name text, date_from text, date_to text, number_hours text, conducted_by text, location text)');
        $this->database->execute('create table pds_other_info (id integer primary key autoincrement, employee_id text, special_skills text, recognition text, membership_organization text)');
        $this->database->execute('create table pds_questions (id integer primary key autoincrement, employee_id text, question_no integer, answer integer, details text)');
        $this->database->execute('create table pds_references (id integer primary key autoincrement, employee_id text, name text, address text, tel_no text, ctc_no text, issue_at text, issue_on text)');
        $this->database->execute('create table service_record (id integer primary key autoincrement, employee_id text, date_from text, date_to text, designation text, status text, salary text, office_entity text, lwop text, branch text, remarks text, separation_date text, separation_cause text)');

        foreach (['item_number', 'position', 'office_id', 'salary_grade', 'step', 'permanent', 'last_promotion', 'last_increment', 'level', 'eligibility', 'first_day_of_service', 'graduated', 'course', 'units', 'post_grad'] as $column) {
            $this->database->execute(sprintf('alter table employee add column %s %s', $column, $text));
        }

        foreach (['birth_date', 'sex', 'res_address'] as $column) {
            $this->database->execute(sprintf('alter table employee add column %s %s', $column, $text));
        }
    }
}
