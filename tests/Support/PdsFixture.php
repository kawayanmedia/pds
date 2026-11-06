<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Support;

use KawayanMedia\Pds\Model\Pds;

/**
 * A complete, legacy-shaped data set: one row for every PDS section.
 */
final class PdsFixture
{
    /**
     * @return array<string, mixed>
     */
    public static function rows(string $employeeId = '013001'): array
    {
        return [
            'employee_id' => $employeeId,
            'personal' => [
                'employee_id' => $employeeId,
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
                'agency_employee_no' => $employeeId,
                'tin' => '123-456-789-000',
            ],
            'family' => [
                'employee_id' => $employeeId,
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
                    'scholarship' => '',
                ],
                [
                    'level' => 2,
                    'school_name' => 'Marinduque National High School',
                    'degree_course' => 'Secondary',
                    'year_graduated' => '2001',
                    'highest_grade' => 'Fourth Year',
                    'attend_from' => '1997',
                    'attend_to' => '2001',
                    'scholarship' => '',
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
        ];
    }

    public static function pds(string $employeeId = '013001'): Pds
    {
        return Pds::fromArray(self::rows($employeeId));
    }
}
