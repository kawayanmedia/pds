# kawayanmedia/pds

Framework-agnostic Personal Data Sheet (PDS / CS Form 212) PDF generator.

It is a port of the legacy iHRMIS `Pds_preview` screen: the same four form pages,
the same field coordinates, the same fonts and the same column widths, but with
database access injected through one small interface. The package draws every
page into a single PDF document — no temporary files, no concatenation step — so
it works in CodeIgniter, Laravel, Symfony or plain PHP.

## Requirements

- PHP 8.2+
- `setasign/fpdi` ^2.6 and `setasign/fpdf` ^1.9 (installed automatically)
- Four PDS templates. Copies of the iHRMIS CS Form 212 pages ship in
  `resources/templates/`, and you can point the layout at your own files.

## Installation

```bash
composer require kawayanmedia/pds
```

While the package lives inside the iHRMIS repository, install it through a path
repository:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/pds-pdf", "options": { "symlink": true } }
    ],
    "require": {
        "kawayanmedia/pds": "^0.1"
    }
}
```

`kawayanmedia/persistence` comes with it as a dependency; it is only used by the
write side, so printing needs no database at all.

## See it running

The package ships with a database-free example that prints two employees:

```bash
php packages/pds-pdf/examples/generate-sample.php
open packages/pds-pdf/examples/output/pds-sample.pdf
```

The script writes `examples/output/pds-sample.pdf` and prints a summary per
employee. Pass a template directory and an output path to override the defaults:

```bash
php vendor/kawayanmedia/pds/examples/generate-sample.php \
    /path/to/pds-templates /tmp/pds-sample.pdf
```

The write side has its own runnable example, which creates a SQLite schema and
saves every PDS section:

```bash
php packages/pds-pdf/examples/write-sample.php
```

## Quick start

```php
use KawayanMedia\Pds\Adapter\ArrayPdsRepository;
use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\PdsGenerator;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;

$generator = new PdsGenerator(
    new ArrayPdsRepository([
        '013001' => [
            'employee_id' => '013001',
            'personal' => [
                'lname' => 'Dela Cruz',
                'fname' => 'Juan',
                'mname' => 'Santos',
                'sex' => 'M',
                'civil_status' => 2,
                'birth_date' => '1985-03-14',
            ],
            'family' => ['spouse_lname' => 'Dela Cruz', 'spouse_fname' => 'Maria'],
            'education' => [
                ['level' => 4, 'school_name' => 'Marinduque State College', 'degree_course' => 'BSIT'],
            ],
            'work' => [
                ['inclusive_date_from' => '2010-01-04', 'inclusive_date_to' => 'Present', 'position' => 'ITO I'],
            ],
        ],
    ]),
    new PdsPdfRenderer(PdsLayout::legacyIhrmis()),
);

$bytes = $generator->render('013001');            // PDF string, four pages
$generator->save('013001', '/tmp/pds.pdf');        // write a file
$generator->renderMany(['013001', '013002']);      // one PDF, four pages each

$generator->renderer()->stream($generator->pds('013001'), 'pds.pdf');   // to the browser
$generator->renderer()->download($generator->pds('013001'), 'pds.pdf');
```

Print only some pages, or change the accomplished date:

```php
$renderer = new PdsPdfRenderer(
    PdsLayout::legacyIhrmis(),
    new PdsOptions(pages: [1], accomplishedOn: '2026-11-06'),
);
```

## Architecture

```
rows from your database ──► PdsRepositoryInterface ──► Pds
                                                        │
                                          ┌─────────────┴──────────────┐
                                          ▼                            ▼
                                   PdsPdfRenderer              PdsOptions
                                          │
                          ┌───────────────┼────────────────┬──────────────────┐
                          ▼               ▼                ▼                  ▼
                    PersonalPage      CareerPage      LearningPage     QuestionsPage
                    (legacy page1)   (legacy page2)   (legacy page3)   (legacy page4)
                          │               │                │                  │
                          └───────────────┴────────────────┴──────────────────┘
                                          │
                              PlacementWriter + TableWriter
                                          │
                              FPDI + your templates ──► %PDF
```

| Legacy | Package |
|---|---|
| `Pds_preview::page1()` | `Renderer\Page\PersonalPage` |
| `Pds_preview::page2()` | `Renderer\Page\CareerPage` |
| `Pds_preview::page3()` | `Renderer\Page\LearningPage` |
| `Pds_preview::page4()` | `Renderer\Page\QuestionsPage` |
| `Pds_preview::pds()` (temp files + `concat_pdf`) | `Renderer\PdsPdfRenderer` |
| `$pdf->SetXY()` / `Write()` chains | `Config\Placement` list in `Config\PdsLayout` |
| `Personal`, `Family`, `Children`, `Education`, `Eligibility`, `Work`, `Organization`, `Training`, `Other_info`, `Question`, `Reference` | `Model\*` value objects |

## The one contract to implement

```php
use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\Model\Pds;

interface PdsRepositoryInterface
{
    public function find(string $employeeId): ?Pds;
}
```

`Pds::fromArray()` maps the legacy rows in one place:

| Key | Legacy table |
|---|---|
| `personal` | `pds_personal_info` |
| `family` | `pds_family_background` |
| `children` | `pds_children` |
| `education` | `pds_education_background` (`level` 1–5) |
| `eligibility` | `pds_eligibility` |
| `work` | `pds_work_experience` |
| `volunteer` | `pds_organization` |
| `training` | `pds_training` |
| `other_info` | `pds_other_info` |
| `questions` | `pds_questions` (`question_no`, `answer`) — or `answers` as `[1 => true, …]` |
| `references` | `pds_references` |

Rows are ordered the way the legacy queries ordered them: children by birth
date, work experience and trainings from the most recent down.

## Configuration

### Layout

Every coordinate, font size and row height lives in `Config\PdsLayout`. The
`PdsLayout::legacyIhrmis()` preset reproduces the legacy controller, using the
bundled templates:

```php
$layout = PdsLayout::legacyIhrmis();                       // bundled resources/templates
$layout = PdsLayout::fromDirectory('/srv/forms/pds');      // page1.pdf … page4.pdf
$layout = PdsLayout::legacyIhrmis([                        // explicit files
    PdsLayout::PAGE_PERSONAL  => '/srv/forms/pds/personal.pdf',
    PdsLayout::PAGE_CAREER    => '/srv/forms/pds/career.pdf',
    PdsLayout::PAGE_LEARNING  => '/srv/forms/pds/learning.pdf',
    PdsLayout::PAGE_QUESTIONS => '/srv/forms/pds/questions.pdf',
]);
```

The templates are placed at `(1, 1)` scaled to 210 mm wide, exactly like the
legacy controller, so every coordinate in the preset is expressed in that same
scaled space. Pass `templateWidth: null` to draw a template at its natural size.

Named adjustments:

| Property | Default | Meaning |
|---|---|---|
| `templateX`, `templateY`, `templateWidth` | `1.0`, `1.0`, `210.0` | Template placement |
| `pageSize`, `pageOrientation`, `pageUnit` | `Legal`, `P`, `mm` | FPDF page |
| `fontFamily`, `baseFontSize` | `Arial`, `12` | Base font of the pages |
| `compress` | `true` | Set `false` for text assertions in tests |
| `personalPage` | `PageLayout` | Absolute fields on page 1 |
| `children`, `eligibility`, `work`, `volunteer`, `training`, `otherInfo`, `references` | `TableLayout` | Repeating blocks |
| `education`, `questions`, `ctc` | arrays | Block anchors and columns |

A `TableLayout` holds the row columns, the y of the first row, the row pitch and
the y at which a fresh copy of the template page is started, so a long list never
runs off the form:

```php
new TableLayout(
    columns: [Placement::text('position', 39.0, 0.0, 8.0, 20)],
    startY: 115.2,
    rowHeight: 7.35,
    endY: 293.0,
    templatePage: PdsLayout::PAGE_CAREER,
);
```

### Options

| Option | Default | Meaning |
|---|---|---|
| `pages` | `[1, 2, 3, 4]` | Which form pages to print |
| `accomplishedOn` | `null` (today) | Date printed on the "Date Accomplished" line |
| `dateFormat` | `F d, Y` | Format of that date |
| `presentLabel` | `Present` | Text used for an open-ended work experience |

## Deviations from the legacy controller

Each row below is a defect in `application/modules/pds/controllers/pds_preview.php`
that this package fixes; everything else is reproduced:

| Legacy defect | Package behaviour |
|---|---|
| `str_split()` read at fixed offsets, so short names raised "undefined array key" | `Support\BoxText` pads and truncates to the 30 boxes |
| Education cells read `$sch[1..3]` / `$degree[1..3]` unconditionally | `Support\WordChunks::lines()` always returns four lines |
| Voluntary work printed `inclusive_date_from` in both date columns | `inclusive_date_to` is printed in the "To" column |
| Voluntary work printed `license_release_date` as the position | `position` is printed |
| Other information reused the training columns (`number_hours`, `conducted_by`) | only skills, recognition and membership are printed |
| Overflow rows landed on a blank page without the form | the same template page is repeated |
| Question marks drifted when a question row was missing | each question has an absolute y |
| Page 4 read `$reference` after the loop (undefined with no references) | the first reference is used, null-safe |
| `splitstroverflow()` pushed a word to the second line as soon as the running length reached the limit, and moved an over-long word off the first line | word-boundary split that keeps as many words as fit |
| Empty dates printed `--` / `//` | empty dates print nothing |
| Eligibility and work rows started one printed row too low | row pitch measured from the bundled templates (7.35 mm in scaled space) |
| Reference columns were printed at the 12 pt base size and overflowed their cells | printed at 10 pt |

## Behaviour preserved from the legacy code

- Page order: personal information and family → career → learning → questions.
- Name fields are printed one character per pre-printed box, across the same 30
  box positions.
- Table casing (`ucwords(strtolower(...))`) and CodeIgniter's
  `character_limiter()` truncation with the `&#8230;` → `..` replacement.
- Column widths used to wrap the education cells (23 and 15 characters, four
  lines) and the two-line address fields (30 characters).
- Date shapes: `m-d-Y` for the birth date, `m/d/Y` for children, work,
  volunteers, trainings, references and the CTC, `F d, Y` for page 4.
- The API returns bytes or writes files; it never sends headers, touches
  sessions or deletes rows. Streaming and downloading stay in your controller.

## Writing data

`Service\PdsEditor` creates and updates the ten PDS sections, so the same package
serves the entry screens and the print preview:

```php
use KawayanMedia\Pds\Adapter\PdoPdsStore;
use KawayanMedia\Pds\Service\PdsEditor;
use KawayanMedia\Persistence\Adapter\PdoDatabase;

$store  = new PdoPdsStore(PdoDatabase::fromDsn('mysql:host=localhost;dbname=ihrmis', $user, $pass));
$editor = new PdsEditor($store);

$editor->savePersonal($employeeId, $request);
$editor->saveFamilyBackground($employeeId, $request, $children);
$editor->saveEducation($employeeId, [1 => $elementary, 4 => $college]);
$editor->saveEligibility($employeeId, $rows);
$editor->saveWorkExperience($employeeId, $rows);
$editor->saveVoluntaryWork($employeeId, $rows);
$editor->saveTraining($employeeId, $rows);
$editor->saveOtherInfo($employeeId, $otherInfo, $questions, $references);
$editor->savePositionDetails($employeeId, $profile);
$editor->saveServiceRecord($employeeId, $serviceRecord);
```

Each call replaces one section in a single transaction, drops the blank rows the
legacy screens dropped, and mirrors `birth_date`, `sex` and `res_address` onto
the employee record. Validation errors arrive as
`Exception\ValidationException::errors()`.

Tables, columns, the `PdsStoreInterface` seam and the behaviour differences are
documented in **[docs/writing-data.md](docs/writing-data.md)**.

## Using it in another project

| Stack | Guide |
|---|---|
| Plain PHP (PDO, no framework) | [docs/plain-php.md](docs/plain-php.md) |
| Laravel (service provider, Eloquent, controller) | [docs/laravel.md](docs/laravel.md) |
| CodeIgniter 2 / 3 / 4 | [docs/codeigniter.md](docs/codeigniter.md) |

## Testing

```bash
vendor/bin/phpunit -c packages/pds-pdf/phpunit.xml.dist
```

The suite covers the box writer, the word splitter, the date shapes, the model
mapping, the array repository, the layout presets and real PDF output. The
feature tests build blank templates with FPDF, so they do not depend on the
scanned form.

## Licence

MIT.
