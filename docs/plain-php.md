# Using `kawayanmedia/pds` in plain PHP

A complete, framework-free integration. The package only needs Composer, PDO and
the four PDS templates.

## 1. Install

```bash
composer require kawayanmedia/pds
```

While the package lives inside the iHRMIS repository, point Composer at the
folder:

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

```bash
composer update kawayanmedia/pds
```

Then load the autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

## 2. Implement the repository

The package never touches a database. One method loads the rows of a single
employee and hands them back as a `Pds` aggregate.

```php
use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\Model\Pds;

final class PdoPdsRepository implements PdsRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(string $employeeId): ?Pds
    {
        $personal = $this->one('pds_personal_info', $employeeId);

        if ($personal === null) {
            return null;
        }

        return Pds::fromArray([
            'employee_id' => $employeeId,
            'personal' => $personal,
            'family' => $this->one('pds_family_background', $employeeId),
            'children' => $this->many('pds_children', $employeeId),
            'education' => $this->many('pds_education_background', $employeeId),
            'eligibility' => $this->many('pds_eligibility', $employeeId),
            'work' => $this->many('pds_work_experience', $employeeId),
            'volunteer' => $this->many('pds_organization', $employeeId),
            'training' => $this->many('pds_training', $employeeId),
            'other_info' => $this->many('pds_other_info', $employeeId),
            'questions' => $this->many('pds_questions', $employeeId),
            'references' => $this->many('pds_references', $employeeId),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function one(string $table, string $employeeId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE employee_id = ? LIMIT 1');
        $statement->execute([$employeeId]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function many(string $table, string $employeeId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE employee_id = ?');
        $statement->execute([$employeeId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

`Pds::fromArray()` accepts models as well as arrays, so a repository built on a
query builder can pass objects.

Ordering: the aggregate orders children by birth date and work experience and
trainings from the most recent down, exactly like the legacy queries. Do not add
`ORDER BY` clauses for those three sections — they are applied for you.

## 3. Render

```php
use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\PdsGenerator;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;

$generator = new PdsGenerator(
    new PdoPdsRepository($pdo),
    new PdsPdfRenderer(
        PdsLayout::fromDirectory(__DIR__ . '/forms/pds'),
        new PdsOptions(accomplishedOn: '2026-11-06'),
    ),
);

// Write a file
$generator->save('013001', __DIR__ . '/storage/pds-013001.pdf');

// Keep the bytes (store them, mail them, put them in a queue)
$bytes = $generator->render('013001');

// Several employees in one document, four pages each
$generator->saveMany(['013001', '013002', '013003'], __DIR__ . '/storage/pds-batch.pdf');
```

`render()` returns the PDF as a string; `save()` writes a file; `stream()` and
`download()` send the headers for you, which is handy in a small script:

```php
$pds = $generator->pds('013001');

$generator->renderer()->download($pds, 'pds-013001.pdf');
```

## 4. Handle the missing record

```php
use KawayanMedia\Pds\Exception\PdsNotFoundException;

if ($generator->find('013001') === null) {
    http_response_code(404);
    exit('No Personal Data Sheet on file for that employee.');
}

try {
    $bytes = $generator->render('013001');
} catch (PdsNotFoundException $exception) {
    // render() by id already calls find() for you
}
```

## 5. Point at your own templates

The bundled templates are the iHRMIS copies of the CS Form 212 pages. If your
agency prints on a different scan, pass its path:

```php
use KawayanMedia\Pds\Config\PdsLayout;

$layout = PdsLayout::legacyIhrmis([
    PdsLayout::PAGE_PERSONAL => __DIR__ . '/forms/agency-page1.pdf',
    PdsLayout::PAGE_CAREER => __DIR__ . '/forms/agency-page2.pdf',
    PdsLayout::PAGE_LEARNING => __DIR__ . '/forms/agency-page3.pdf',
    PdsLayout::PAGE_QUESTIONS => __DIR__ . '/forms/agency-page4.pdf',
]);
```

If the replacement template has different margins, adjust `templateX`,
`templateY` and `templateWidth`; if its rows are taller or shorter, adjust the
`startY`, `rowHeight` and `endY` of the matching `TableLayout`.

```php
$layout = new PdsLayout(
    templates: PdsLayout::templatesIn(__DIR__ . '/forms/pds'),
    templateWidth: null,                 // draw each page at its natural size
    compress: false,                     // keep the streams readable in tests
);
```

## 6. Check the output

```bash
php -r 'require "vendor/autoload.php"; /* … */'
open storage/pds-013001.pdf
```

One `Pds` always produces four pages, in the order personal → career →
learning → questions. A section that overflows (children, eligibility, work,
volunteer work, trainings, other information, references) continues on another
copy of the same template page.
