<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Service;

use KawayanMedia\Pds\Contract\PdsStoreInterface;
use KawayanMedia\Pds\Exception\ValidationException;
use KawayanMedia\Pds\Model\EducationLevel;
use KawayanMedia\Pds\Model\PdsProfile;
use KawayanMedia\Pds\Model\ServiceRecordEntry;
use KawayanMedia\Pds\Support\PdsSection;
use KawayanMedia\Pds\Support\Text;

/**
 * Creates and updates PDS data section by section, mirroring the ten save
 * handlers of the legacy `pds` module.
 *
 * Each method is a full replace of the section it owns, exactly like the legacy
 * screens: blank rows are dropped the way the controller dropped them, and the
 * write happens in one transaction.
 */
final class PdsEditor
{
    /**
     * Columns each section accepts. Unknown keys are ignored so a form can post
     * its whole request without the caller filtering it first.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        PdsSection::PERSONAL => [
            'lname', 'fname', 'mname', 'extension', 'birth_date', 'birth_place', 'sex', 'civil_status',
            'citizenship', 'height', 'weight', 'blood_type', 'gsis', 'pagibig', 'philhealth', 'sss',
            'res_address', 'res_zip', 'res_tel', 'permanent_address', 'permanent_zip', 'permanent_tel',
            'email', 'cp', 'agency_employee_no', 'tin',
        ],
        PdsSection::FAMILY => [
            'spouse_lname', 'spouse_fname', 'spouse_mname', 'spouse_occupation', 'spouse_employer',
            'spouse_biz_ad', 'spouse_tel', 'father_lname', 'father_fname', 'father_mname',
            'mother_lname', 'mother_fname', 'mother_mname',
        ],
        PdsSection::CHILDREN => ['children', 'birth_date'],
        PdsSection::EDUCATION => [
            'school_name', 'degree_course', 'year_graduated', 'highest_grade', 'attend_from', 'attend_to', 'scholarship',
        ],
        PdsSection::ELIGIBILITY => [
            'type', 'rating', 'date_exam_conferment', 'place_exam_conferment', 'license_no', 'license_release_date',
        ],
        PdsSection::WORK => [
            'inclusive_date_from', 'inclusive_date_to', 'position', 'company', 'monthly_salary',
            'salary_grade', 'status', 'movement', 'govt_service',
        ],
        PdsSection::VOLUNTEER => ['name', 'inclusive_date_from', 'inclusive_date_to', 'number_of_hours', 'position'],
        PdsSection::TRAINING => ['name', 'date_from', 'date_to', 'number_hours', 'conducted_by', 'location'],
        PdsSection::OTHER_INFO => ['special_skills', 'recognition', 'membership_organization'],
        PdsSection::QUESTIONS => ['question_no', 'answer', 'details'],
        PdsSection::REFERENCES => ['name', 'address', 'tel_no', 'ctc_no', 'issue_at', 'issue_on'],
        PdsSection::SERVICE_RECORD => ServiceRecordEntry::COLUMNS,
    ];

    /**
     * Columns of the employee record the personal information screen mirrors.
     *
     * @var list<string>
     */
    private const EMPLOYEE_MIRROR = ['birth_date', 'sex', 'res_address'];

    public function __construct(private readonly PdsStoreInterface $store)
    {
    }

    /**
     * Section 1: name, birth, contact and government numbers. Also mirrors the
     * birth date, sex and residential address onto the employee record, the way
     * the legacy controller did.
     *
     * @param array<string, mixed> $values
     */
    public function savePersonal(string $employeeId, array $values): void
    {
        $this->assertEmployeeId($employeeId);

        $row = $this->row(PdsSection::PERSONAL, $values);

        $errors = [];

        if (($row['lname'] ?? '') === '') {
            $errors['lname'] = 'The surname is required.';
        }

        if (($row['fname'] ?? '') === '') {
            $errors['fname'] = 'The first name is required.';
        }

        if ($errors !== []) {
            throw ValidationException::withErrors($errors);
        }

        $mirror = array_intersect_key($row, array_flip(self::EMPLOYEE_MIRROR));

        $this->store->transaction(function () use ($employeeId, $row, $mirror): void {
            $this->store->save(PdsSection::PERSONAL, $employeeId, $row);
            $this->store->sync(PdsSection::PROFILE, $employeeId, $mirror);
        });
    }

    /**
     * Section 2: spouse, parents and children. Children are replaced wholesale,
     * and rows without a name are dropped.
     *
     * @param array<string, mixed>       $values
     * @param list<array<string, mixed>> $children
     */
    public function saveFamilyBackground(string $employeeId, array $values, array $children = []): void
    {
        $this->assertEmployeeId($employeeId);

        $row = $this->row(PdsSection::FAMILY, $values);
        $rows = $this->rows(PdsSection::CHILDREN, $children, 'children');

        $this->store->transaction(function () use ($employeeId, $row, $rows): void {
            $this->store->save(PdsSection::FAMILY, $employeeId, $row);
            $this->store->replace(PdsSection::CHILDREN, $employeeId, $rows);
        });
    }

    /**
     * Section 3: the five pre-printed education rows. Pass a map of level =>
     * values, where level is 1-5 or an EducationLevel case. A level whose
     * `school_name` is blank is cleared.
     *
     * @param array<int|string, array<string, mixed>> $levels
     */
    public function saveEducation(string $employeeId, array $levels): void
    {
        $this->assertEmployeeId($employeeId);

        $normalised = [];

        foreach ($levels as $level => $values) {
            $case = EducationLevel::fromValue($level);

            if ($case === null) {
                throw ValidationException::forField('level', sprintf('Unknown education level "%s".', (string) $level));
            }

            $normalised[$case->value] = $this->row(PdsSection::EDUCATION, (array) $values);
        }

        if ($normalised === []) {
            return;
        }

        $this->store->transaction(function () use ($employeeId, $normalised): void {
            foreach ($normalised as $level => $row) {
                $rows = ($row['school_name'] ?? '') === '' ? [] : [$row];

                $this->store->replace(PdsSection::EDUCATION, $employeeId, $rows, ['level' => $level]);
            }
        });
    }

    /**
     * Section 4: civil service eligibilities. Rows without a `type` are dropped.
     *
     * @param list<array<string, mixed>> $eligibilities
     */
    public function saveEligibility(string $employeeId, array $eligibilities): void
    {
        $this->assertEmployeeId($employeeId);

        $rows = $this->rows(PdsSection::ELIGIBILITY, $eligibilities, 'type');

        $this->store->replace(PdsSection::ELIGIBILITY, $employeeId, $rows);
    }

    /**
     * Section 5: work experience. Rows without `inclusive_date_from` are dropped.
     *
     * @param list<array<string, mixed>> $workExperience
     */
    public function saveWorkExperience(string $employeeId, array $workExperience): void
    {
        $this->assertEmployeeId($employeeId);

        $rows = $this->rows(PdsSection::WORK, $workExperience, 'inclusive_date_from');

        $this->store->replace(PdsSection::WORK, $employeeId, $rows);
    }

    /**
     * Section 6: voluntary work. Rows without a `name` are dropped.
     *
     * @param list<array<string, mixed>> $voluntaryWork
     */
    public function saveVoluntaryWork(string $employeeId, array $voluntaryWork): void
    {
        $this->assertEmployeeId($employeeId);

        $rows = $this->rows(PdsSection::VOLUNTEER, $voluntaryWork, 'name');

        $this->store->replace(PdsSection::VOLUNTEER, $employeeId, $rows);
    }

    /**
     * Section 7: training programs. Rows without a `name` are dropped.
     *
     * @param list<array<string, mixed>> $trainings
     */
    public function saveTraining(string $employeeId, array $trainings): void
    {
        $this->assertEmployeeId($employeeId);

        $rows = $this->rows(PdsSection::TRAINING, $trainings, 'name');

        $this->store->replace(PdsSection::TRAINING, $employeeId, $rows);
    }

    /**
     * Section 8: other information, the seven yes/no questions and references.
     *
     * Questions are keyed by `question_no` (starting at 1); answers accept 1/0,
     * true/false or "yes"/"no" and are stored as 1 and 0. References whose name,
     * address and telephone are all blank are dropped.
     *
     * @param list<array<string, mixed>> $otherInfo
     * @param list<array<string, mixed>> $questions
     * @param list<array<string, mixed>> $references
     */
    public function saveOtherInfo(
        string $employeeId,
        array $otherInfo = [],
        array $questions = [],
        array $references = [],
    ): void {
        $this->assertEmployeeId($employeeId);

        $infoRows = $this->rows(PdsSection::OTHER_INFO, $otherInfo);
        $questionRows = $this->questionRows($questions);
        $referenceRows = $this->rows(PdsSection::REFERENCES, $references, null, ['name', 'address', 'tel_no']);

        $this->store->transaction(function () use ($employeeId, $infoRows, $questionRows, $referenceRows): void {
            $this->store->replace(PdsSection::OTHER_INFO, $employeeId, $infoRows);
            $this->store->replace(PdsSection::QUESTIONS, $employeeId, $questionRows);
            $this->store->replace(PdsSection::REFERENCES, $employeeId, $referenceRows);
        });
    }

    /**
     * The position details screen. Writes the employment columns of the
     * employee record; it never creates an employee.
     *
     * @param PdsProfile|array<string, mixed> $values
     */
    public function savePositionDetails(string $employeeId, PdsProfile|array $values): void
    {
        $this->assertEmployeeId($employeeId);

        $row = $values instanceof PdsProfile
            ? $this->row(PdsProfile::COLUMNS, $values->toArray())
            : $this->row(PdsProfile::COLUMNS, $values);

        $this->store->transaction(function () use ($employeeId, $row): void {
            $this->store->sync(PdsSection::PROFILE, $employeeId, $row);
        });
    }

    /**
     * Section 9: service record rows. Rows without a `date_from` are dropped.
     *
     * @param list<array<string, mixed>|ServiceRecordEntry> $serviceRecord
     */
    public function saveServiceRecord(string $employeeId, array $serviceRecord): void
    {
        $this->assertEmployeeId($employeeId);

        $entries = array_map(
            static fn (mixed $entry): array => $entry instanceof ServiceRecordEntry ? $entry->toArray() : (array) $entry,
            $serviceRecord,
        );

        $rows = $this->rows(PdsSection::SERVICE_RECORD, $entries, 'date_from');

        $this->store->replace(PdsSection::SERVICE_RECORD, $employeeId, $rows);
    }

    /**
     * Reads a section back through the store, which is handy after a save.
     *
     * @return list<array<string, mixed>>
     */
    public function load(string $section, string $employeeId): array
    {
        return $this->store->find($section, $employeeId);
    }

    private function assertEmployeeId(string $employeeId): void
    {
        if (trim($employeeId) === '') {
            throw ValidationException::forField('employee_id', 'An employee id is required.');
        }
    }

    /**
     * @param list<string>|string   $section section name or an explicit column list
     * @param array<string, mixed>  $values
     *
     * @return array<string, mixed>
     */
    private function row(array|string $section, array $values): array
    {
        $columns = is_string($section) ? (self::COLUMNS[$section] ?? []) : $section;
        $row = [];

        foreach ($columns as $column) {
            if (!array_key_exists($column, $values)) {
                continue;
            }

            $value = $values[$column];

            if (is_string($value)) {
                $value = trim($value);
            }

            $row[$column] = $value;
        }

        return $row;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string>               $anyOf keep rows where at least one of these columns is filled
     *
     * @return list<array<string, mixed>>
     */
    private function rows(string $section, array $rows, ?string $required = null, array $anyOf = []): array
    {
        $kept = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw ValidationException::forField($section, sprintf('Every %s row must be an array.', $section));
            }

            $normalised = $this->row($section, $row);

            if ($required !== null && Text::blank($normalised[$required] ?? '') === '') {
                continue;
            }

            if ($anyOf !== [] && $this->isBlank($normalised, $anyOf)) {
                continue;
            }

            $kept[] = $normalised;
        }

        return $kept;
    }

    /**
     * @param list<array<string, mixed>> $questions
     *
     * @return list<array<string, mixed>>
     */
    private function questionRows(array $questions): array
    {
        $kept = [];

        foreach ($questions as $question) {
            if (!is_array($question)) {
                throw ValidationException::forField('questions', 'Every question row must be an array.');
            }

            $row = $this->row(PdsSection::QUESTIONS, $question);

            if (!array_key_exists('question_no', $row)) {
                throw ValidationException::forField('question_no', 'Every question row needs a question number.');
            }

            $number = (int) $row['question_no'];

            if ($number < 1) {
                throw ValidationException::forField('question_no', 'Question numbers start at 1.');
            }

            $row['question_no'] = $number;
            $row['answer'] = self::answer($row['answer'] ?? 0);

            $kept[] = $row;
        }

        return $kept;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string>         $columns
     */
    private function isBlank(array $row, array $columns): bool
    {
        foreach ($columns as $column) {
            if (Text::blank($row[$column] ?? '') !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalises a PDS yes/no answer to the legacy 1 or 0.
     */
    public static function answer(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value) || is_float($value)) {
            return $value == 0 ? 0 : 1;
        }

        return match (strtolower(trim((string) $value))) {
            '', '0', 'no', 'n', 'false' => 0,
            default => 1,
        };
    }
}
