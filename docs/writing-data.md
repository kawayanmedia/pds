# Writing PDS data

The package does not only print: `Service\PdsEditor` creates and updates the ten
sections of the legacy PDS screens.

```php
use KawayanMedia\Pds\Adapter\PdoPdsStore;
use KawayanMedia\Pds\Service\PdsEditor;
use KawayanMedia\Persistence\Adapter\PdoDatabase;

$store  = new PdoPdsStore(PdoDatabase::fromDsn('mysql:host=localhost;dbname=ihrmis', $user, $pass));
$editor = new PdsEditor($store);

$editor->savePersonal('013001', $request);            // pds_personal_info (+ employee mirror)
$editor->saveFamilyBackground('013001', $request, $children);
$editor->saveEducation('013001', [1 => $elementary, 4 => $college]);
$editor->saveEligibility('013001', $rows);
$editor->saveWorkExperience('013001', $rows);
$editor->saveVoluntaryWork('013001', $rows);
$editor->saveTraining('013001', $rows);
$editor->saveOtherInfo('013001', $otherInfo, $questions, $references);
$editor->savePositionDetails('013001', $profile);     // employee record, update only
$editor->saveServiceRecord('013001', $serviceRecord);
```

## Behaviour

- **One section per call.** Every method is a full replace of the section it
  owns, exactly like the legacy submit handlers, and runs in a single
  transaction.
- **Unknown keys are ignored.** Pass the whole request array; only known columns
  reach the database.
- **Blank rows are dropped** where the legacy screens dropped them: children
  without a name, eligibilities without a type, work experience without a start
  date, voluntary work and training without a name, service record rows without
  a start date, and references whose name, address and telephone are all blank.
- **Education is replaced per level.** A level whose `school_name` is blank is
  cleared and the other levels are untouched.
- **Personal information mirrors the employee record.** `birth_date`, `sex` and
  `res_address` are also written to the employee row (`employee` by default),
  the way the legacy controller did. `savePositionDetails()` only ever updates
  an existing employee; it never creates one.
- **Questions** are keyed by `question_no` (starting at 1). Answers accept
  `1/0`, `true/false` or `"yes"/"no"` and are stored as `1` and `0`.

## Validation

`Exception\ValidationException` carries one message per field:

```php
try {
    $editor->savePersonal($id, $request);
} catch (ValidationException $exception) {
    $errors = $exception->errors();   // ['lname' => 'The surname is required.', ...]
}
```

Rejected input: a blank employee id, a blank surname or first name, an education
level outside 1–5, and a question row without a number.

## Tables and columns

`Config\PdsTables` maps each section to a table and key column and can be
overridden, so a host does not have to rename anything:

```php
$tables = PdsTables::fromArray(
    [PdsSection::PERSONAL => 'hr_personal'],
    [PdsSection::PERSONAL => 'employee_no'],
);
$store = new PdoPdsStore($database, $tables);
```

| Section | Default table | Key |
|---|---|---|
| `personal` | `pds_personal_info` | `employee_id` |
| `family` | `pds_family_background` | `employee_id` |
| `children` | `pds_children` | `employee_id` |
| `education` | `pds_education_background` | `employee_id` + `level` |
| `eligibility` | `pds_eligibility` | `employee_id` |
| `work` | `pds_work_experience` | `employee_id` |
| `volunteer` | `pds_organization` | `employee_id` |
| `training` | `pds_training` | `employee_id` |
| `other_info` | `pds_other_info` | `employee_id` |
| `questions` | `pds_questions` | `employee_id` |
| `references` | `pds_references` | `employee_id` |
| `service_record` | `service_record` | `employee_id` |
| `profile` (employee) | `employee` | `id` |

Columns written per section are listed in `Service\PdsEditor::COLUMNS`, and
`Model\PdsProfile::COLUMNS` / `Model\ServiceRecordEntry::COLUMNS` cover the two
sections that also have typed models. The package never creates tables.

## Custom persistence

Implement `Contract\PdsStoreInterface` to write through your own ORM:

```php
interface PdsStoreInterface
{
    public function find(string $section, string $employeeId): array;
    public function first(string $section, string $employeeId): ?array;
    public function save(string $section, string $employeeId, array $values): void;   // insert or update
    public function sync(string $section, string $employeeId, array $values): void;   // update only
    public function replace(string $section, string $employeeId, array $rows, array $scope = []): void;
    public function transaction(callable $operation): mixed;
}
```

`Adapter\ArrayPdsStore` is the in-memory implementation used by the tests and by
`examples/write-sample.php`.

## Differences from the legacy controller

- The legacy screens saved each section only when `Input::get('op')` was set;
  the package always writes when a save method is called.
- Blank education and reference rows are cleared instead of silently keeping the
  previous data.
- Only the columns present in the input are written, so a partial payload no
  longer blanks the rest of the row (the legacy controller wrote every column,
  including empty ones).
- A failed write throws; the legacy controller ignored database errors.
