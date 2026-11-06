<?php

declare(strict_types=1);

/**
 * Writes a Personal Data Sheet through the package services, using SQLite so it
 * can be run without a database server.
 *
 * Usage:
 *   php packages/pds-pdf/examples/write-sample.php [database.sqlite]
 *
 * With no argument the schema is created in a temporary file that is removed
 * again, so the script is safe to run anywhere.
 */

use KawayanMedia\Pds\Adapter\PdoPdsStore;
use KawayanMedia\Pds\Model\EducationLevel;
use KawayanMedia\Pds\Model\PdsProfile;
use KawayanMedia\Pds\Model\ServiceRecordEntry;
use KawayanMedia\Pds\Service\PdsEditor;
use KawayanMedia\Pds\Support\PdsSection;
use KawayanMedia\Persistence\Adapter\PdoDatabase;

$autoloaders = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/autoload.php',
    dirname(__DIR__, 4) . '/vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;
        break;
    }
}

if (!class_exists(PdsEditor::class)) {
    fwrite(STDERR, "Composer autoloader not found. Run composer install first.\n");
    exit(1);
}

$temporary = !isset($argv[1]);
$file = $temporary ? tempnam(sys_get_temp_dir(), 'pds-write-') . '.sqlite' : $argv[1];

$database = PdoDatabase::fromDsn('sqlite:' . $file);

if ($temporary) {
    createSchema($database);
}

$editor = new PdsEditor(new PdoPdsStore($database));
$employeeId = '013001';

$editor->savePersonal($employeeId, [
    'lname' => 'Dela Cruz',
    'fname' => 'Juan',
    'mname' => 'Santos',
    'birth_date' => '1985-03-14',
    'birth_place' => 'Boac, Marinduque',
    'sex' => 'M',
    'civil_status' => '2',
    'citizenship' => 'Filipino',
    'res_address' => '123 Sample Street, Boac, Marinduque',
    'email' => 'juan.delacruz@example.gov.ph',
    'cp' => '0917 000 0000',
    'tin' => '123-456-789-000',
]);

$editor->saveFamilyBackground(
    $employeeId,
    ['spouse_lname' => 'Dela Cruz', 'spouse_fname' => 'Maria', 'spouse_occupation' => 'Teacher'],
    [
        ['children' => 'Ana Dela Cruz', 'birth_date' => '2010-01-01'],
        ['children' => '', 'birth_date' => '2012-01-01'],
        ['children' => 'Ben Dela Cruz', 'birth_date' => '2014-06-12'],
    ],
);

$editor->saveEducation($employeeId, [
    EducationLevel::Elementary->value => ['school_name' => 'Boac North Central School', 'year_graduated' => '1997'],
    EducationLevel::Secondary->value => ['school_name' => 'Marinduque National High School', 'year_graduated' => '2001'],
    EducationLevel::College->value => [
        'school_name' => 'Marinduque State College',
        'degree_course' => 'BS Information Technology',
        'year_graduated' => '2005',
    ],
]);

$editor->saveEligibility($employeeId, [
    ['type' => 'Career Service Professional', 'rating' => '85.50', 'date_exam_conferment' => '2006-05-12'],
    ['type' => '', 'rating' => '80.00'],
]);

$editor->saveWorkExperience($employeeId, [
    [
        'inclusive_date_from' => '2015-01-01',
        'position' => 'Information Technology Officer I',
        'company' => 'Provincial Government of Marinduque',
        'monthly_salary' => '45000',
        'salary_grade' => '19',
        'status' => 'Permanent',
        'govt_service' => '1',
    ],
    ['inclusive_date_from' => '', 'position' => 'Ignored because the date is blank'],
]);

$editor->saveVoluntaryWork($employeeId, [['name' => 'Philippine Red Cross', 'position' => 'Volunteer']]);
$editor->saveTraining($employeeId, [
    ['name' => 'Records Management', 'date_from' => '2024-03-04', 'date_to' => '2024-03-06', 'number_hours' => '24'],
]);

$editor->saveOtherInfo(
    $employeeId,
    [['special_skills' => 'Systems administration', 'recognition' => 'Employee of the Year 2023']],
    [
        ['question_no' => 1, 'answer' => 'no'],
        ['question_no' => 2, 'answer' => 'yes', 'details' => 'Administrative case 2019-04, dismissed'],
    ],
    [['name' => 'Maria L. Santos', 'address' => 'Boac, Marinduque', 'tel_no' => '042-123-4567']],
);

$editor->saveServiceRecord($employeeId, [
    new ServiceRecordEntry(
        dateFrom: '2015-01-01',
        designation: 'Information Technology Officer I',
        status: 'Permanent',
        salary: '45000',
        officeEntity: 'Provincial Government of Marinduque',
    ),
]);

// Position details only update an existing employee row.
$database->insert('employee', ['id' => $employeeId]);
$editor->savePositionDetails($employeeId, new PdsProfile(
    itemNumber: 'MARIT-ITO1-12',
    position: 'Information Technology Officer I',
    officeId: 7,
    salaryGrade: 19,
    step: 2,
    firstDayOfService: '2015-01-01',
));

// Reading the sections back is the same store.
foreach ([
    PdsSection::PERSONAL,
    PdsSection::CHILDREN,
    PdsSection::EDUCATION,
    PdsSection::ELIGIBILITY,
    PdsSection::WORK,
    PdsSection::VOLUNTEER,
    PdsSection::TRAINING,
    PdsSection::OTHER_INFO,
    PdsSection::QUESTIONS,
    PdsSection::REFERENCES,
    PdsSection::SERVICE_RECORD,
    PdsSection::PROFILE,
] as $section) {
    printf("%-15s %d row(s)\n", $section, count($editor->load($section, $employeeId)));
}

if ($temporary) {
    unlink($file);
}

function createSchema(PdoDatabase $database): void
{
    $database->execute('create table employee (id text primary key)');

    foreach (['item_number', 'position', 'office_id', 'salary_grade', 'step', 'permanent', 'last_promotion', 'last_increment', 'level', 'eligibility', 'first_day_of_service', 'graduated', 'course', 'units', 'post_grad', 'birth_date', 'sex', 'res_address'] as $column) {
        $database->execute(sprintf('alter table employee add column %s text', $column));
    }

    $database->execute('create table pds_personal_info (employee_id text primary key, lname text, fname text, mname text, extension text, birth_date text, birth_place text, sex text, civil_status text, citizenship text, height text, weight text, blood_type text, gsis text, pagibig text, philhealth text, sss text, res_address text, res_zip text, res_tel text, permanent_address text, permanent_zip text, permanent_tel text, email text, cp text, agency_employee_no text, tin text)');
    $database->execute('create table pds_family_background (employee_id text primary key, spouse_lname text, spouse_fname text, spouse_mname text, spouse_occupation text, spouse_employer text, spouse_biz_ad text, spouse_tel text, father_lname text, father_fname text, father_mname text, mother_lname text, mother_fname text, mother_mname text)');
    $database->execute('create table pds_children (id integer primary key autoincrement, employee_id text, children text, birth_date text)');
    $database->execute('create table pds_education_background (id integer primary key autoincrement, employee_id text, level integer, school_name text, degree_course text, year_graduated text, highest_grade text, attend_from text, attend_to text, scholarship text)');
    $database->execute('create table pds_eligibility (id integer primary key autoincrement, employee_id text, type text, rating text, date_exam_conferment text, place_exam_conferment text, license_no text, license_release_date text)');
    $database->execute('create table pds_work_experience (id integer primary key autoincrement, employee_id text, inclusive_date_from text, inclusive_date_to text, position text, company text, monthly_salary text, salary_grade text, status text, movement text, govt_service text)');
    $database->execute('create table pds_organization (id integer primary key autoincrement, employee_id text, name text, inclusive_date_from text, inclusive_date_to text, number_of_hours text, position text)');
    $database->execute('create table pds_training (id integer primary key autoincrement, employee_id text, name text, date_from text, date_to text, number_hours text, conducted_by text, location text)');
    $database->execute('create table pds_other_info (id integer primary key autoincrement, employee_id text, special_skills text, recognition text, membership_organization text)');
    $database->execute('create table pds_questions (id integer primary key autoincrement, employee_id text, question_no integer, answer integer, details text)');
    $database->execute('create table pds_references (id integer primary key autoincrement, employee_id text, name text, address text, tel_no text, ctc_no text, issue_at text, issue_on text)');
    $database->execute('create table service_record (id integer primary key autoincrement, employee_id text, date_from text, date_to text, designation text, status text, salary text, office_entity text, lwop text, branch text, remarks text, separation_date text, separation_cause text)');
}
