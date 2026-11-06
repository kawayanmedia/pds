<?php

declare(strict_types=1);

/**
 * Generates a sample Personal Data Sheet so the package can be inspected
 * without a database.
 *
 * Usage:
 *   php packages/pds-pdf/examples/generate-sample.php [template-directory] [output.pdf]
 *
 * By default it uses the four templates bundled with the package
 * (resources/templates/page1.pdf … page4.pdf) and writes
 * examples/output/pds-sample.pdf.
 */

use KawayanMedia\Pds\Adapter\ArrayPdsRepository;
use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\PdsGenerator;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;

$autoloaders = [
    dirname(__DIR__) . '/vendor/autoload.php',          // standalone package checkout
    dirname(__DIR__, 3) . '/vendor/autoload.php',       // path repository inside a host project
    dirname(__DIR__, 3) . '/autoload.php',              // installed under vendor/kawayanmedia
    dirname(__DIR__, 4) . '/vendor/autoload.php',       // fallback for custom layouts
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;
        break;
    }
}

if (!class_exists(PdsGenerator::class)) {
    fwrite(STDERR, "Composer autoloader not found. Run composer install first.\n");
    exit(1);
}

$packageRoot = dirname(__DIR__);
$templateDirectory = $argv[1] ?? $packageRoot . '/resources/templates';
$output = $argv[2] ?? $packageRoot . '/examples/output/pds-sample.pdf';

if (!is_file($templateDirectory . '/page1.pdf')) {
    fwrite(STDERR, "Template page1.pdf not found in: {$templateDirectory}\n");
    exit(1);
}

if (!is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}

/**
 * Two employees, described exactly like the legacy iHRMIS rows.
 */
$records = [
    '013001' => [
        'employee_id' => '013001',
        'personal' => [
            'lname' => 'Dela Cruz',
            'fname' => 'Juan',
            'mname' => 'Santos',
            'extension' => 'Jr',
            'birth_date' => '1985-03-14',
            'birth_place' => 'Boac, Marinduque',
            'sex' => 'M',
            'civil_status' => 2,
            'citizenship' => 'Filipino',
            'height' => '1.72',
            'weight' => '70',
            'blood_type' => 'O+',
            'gsis' => '123456789',
            'pagibig' => '1234-5678-9012',
            'philhealth' => '12-345678901-2',
            'sss' => '12-3456789-0',
            'res_address' => '123 Sample Street, Barangay Malusak, Boac, Marinduque',
            'res_zip' => '4900',
            'res_tel' => '(042) 123-4567',
            'permanent_address' => '456 Permanent Road, Barangay Isok, Boac',
            'permanent_zip' => '4900',
            'permanent_tel' => '(042) 765-4321',
            'email' => 'juan@example.test',
            'cp' => '09171234567',
            'agency_employee_no' => '013001',
            'tin' => '123-456-789-000',
        ],
        'family' => [
            'spouse_lname' => 'Dela Cruz',
            'spouse_fname' => 'Maria',
            'spouse_mname' => 'Lopez',
            'spouse_occupation' => 'Teacher',
            'spouse_employer' => 'DepEd',
            'spouse_biz_ad' => 'Boac, Marinduque',
            'spouse_tel' => '09181234567',
            'father_lname' => 'Dela Cruz',
            'father_fname' => 'Pedro',
            'father_mname' => 'Reyes',
            'mother_lname' => 'Santos',
            'mother_fname' => 'Ana',
            'mother_mname' => 'Cruz',
        ],
        'children' => [
            ['children' => 'Jose Dela Cruz', 'birth_date' => '2012-06-01'],
            ['children' => 'Liza Dela Cruz', 'birth_date' => '2015-09-20'],
        ],
        'education' => [
            [
                'level' => 1,
                'school_name' => 'Boac Central Elementary School',
                'degree_course' => 'Elementary',
                'year_graduated' => '1997',
                'highest_grade' => 'Grade VI',
                'attend_from' => '1991',
                'attend_to' => '1997',
            ],
            [
                'level' => 2,
                'school_name' => 'Marinduque National High School',
                'degree_course' => 'Secondary',
                'year_graduated' => '2001',
                'highest_grade' => 'Fourth Year',
                'attend_from' => '1997',
                'attend_to' => '2001',
            ],
            [
                'level' => 4,
                'school_name' => 'Marinduque State College',
                'degree_course' => 'Bachelor of Science in Information Technology',
                'year_graduated' => '2005',
                'highest_grade' => 'Cum Laude',
                'attend_from' => '2001',
                'attend_to' => '2005',
                'scholarship' => 'CHED Scholar',
            ],
        ],
        'eligibility' => [
            [
                'type' => 'Career Service Professional',
                'rating' => '85.20',
                'date_exam_conferment' => '2006-04-23',
                'place_exam_conferment' => 'Boac',
                'license_no' => '1234567',
                'license_release_date' => '2006-06-01',
            ],
        ],
        'work' => [
            [
                'inclusive_date_from' => '2010-01-04',
                'inclusive_date_to' => 'Present',
                'position' => 'Information Technology Officer I',
                'company' => 'Provincial Government of Marinduque',
                'monthly_salary' => '45000',
                'salary_grade' => '19-1',
                'status' => 'Permanent',
                'govt_service' => 1,
            ],
            [
                'inclusive_date_from' => '2006-02-01',
                'inclusive_date_to' => '2009-12-31',
                'position' => 'Computer Operator',
                'company' => 'Marinduque State College',
                'monthly_salary' => '18000',
                'salary_grade' => '9-1',
                'status' => 'Contractual',
                'govt_service' => 0,
            ],
        ],
        'volunteer' => [
            [
                'name' => 'Barangay Malusak Youth Council',
                'inclusive_date_from' => '2003-01-01',
                'inclusive_date_to' => '2004-12-31',
                'number_of_hours' => '120',
                'position' => 'Secretary',
            ],
        ],
        'training' => [
            [
                'name' => 'Records Management and Digitisation Workshop',
                'date_from' => '2025-08-11',
                'date_to' => '2025-08-13',
                'number_hours' => '24',
                'conducted_by' => 'Civil Service Commission',
            ],
            [
                'name' => 'Data Privacy Act Orientation',
                'date_from' => '2024-05-02',
                'date_to' => '2024-05-02',
                'number_hours' => '8',
                'conducted_by' => 'National Privacy Commission',
            ],
        ],
        'other_info' => [
            [
                'special_skills' => 'Database administration',
                'recognition' => 'Outstanding Employee 2024',
                'membership_organization' => 'Philippine Computer Society',
            ],
        ],
        'questions' => [
            ['question_no' => 1, 'answer' => 0],
            ['question_no' => 2, 'answer' => 0],
            ['question_no' => 3, 'answer' => 0],
            ['question_no' => 4, 'answer' => 0],
            ['question_no' => 5, 'answer' => 0],
            ['question_no' => 6, 'answer' => 0],
            ['question_no' => 7, 'answer' => 0],
            ['question_no' => 8, 'answer' => 0],
            ['question_no' => 9, 'answer' => 0],
            ['question_no' => 10, 'answer' => 0],
        ],
        'references' => [
            [
                'name' => 'Maria Santos',
                'address' => 'Boac, Marinduque',
                'tel_no' => '09171234567',
                'ctc_no' => '12345678',
                'issue_at' => 'Boac, Marinduque',
                'issue_on' => '2026-01-05',
            ],
        ],
    ],
    '013002' => [
        'employee_id' => '013002',
        'personal' => [
            'lname' => 'Bautista',
            'fname' => 'Maria',
            'mname' => 'Lopez',
            'birth_date' => '1990-07-02',
            'birth_place' => 'Gasan, Marinduque',
            'sex' => 'F',
            'civil_status' => 1,
            'citizenship' => 'Filipino',
            'res_address' => 'Sitio Ilaya, Barangay Dos, Gasan',
            'permanent_address' => 'Sitio Ilaya, Barangay Dos, Gasan',
            'email' => 'maria@example.test',
            'cp' => '09181234568',
        ],
        'education' => [
            [
                'level' => 4,
                'school_name' => 'Marinduque State College',
                'degree_course' => 'Bachelor of Science in Accountancy',
                'year_graduated' => '2012',
                'attend_from' => '2008',
                'attend_to' => '2012',
            ],
        ],
        'work' => [
            [
                'inclusive_date_from' => '2013-06-01',
                'inclusive_date_to' => 'Present',
                'position' => 'Administrative Officer II',
                'company' => 'Provincial Government of Marinduque',
                'status' => 'Permanent',
                'govt_service' => 1,
            ],
        ],
        'references' => [
            ['name' => 'Pedro Reyes', 'address' => 'Gasan, Marinduque', 'tel_no' => '09190001111'],
        ],
    ],
];

$generator = new PdsGenerator(
    new ArrayPdsRepository($records),
    new PdsPdfRenderer(
        PdsLayout::fromDirectory($templateDirectory),
        new PdsOptions(accomplishedOn: '2026-11-06'),
    ),
);

$generator->saveMany(array_keys($records), $output);

foreach (array_keys($records) as $employeeId) {
    $pds = $generator->pds($employeeId);

    printf(
        "%s %s: %d children, %d education rows, %d work rows, %d trainings, %d references\n",
        $pds->employeeId,
        $pds->personal->fullName(),
        count($pds->children),
        count($pds->education),
        count($pds->work),
        count($pds->training),
        count($pds->references),
    );
}

printf("PDS written to %s (%d forms, 4 pages each)\n", $output, count($records));
