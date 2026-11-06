# Using `kawayanmedia/pds` in CodeIgniter

The package has no framework dependency. The only thing CodeIgniter supplies is
the database connection, and that stays in your repository implementation.

## 1. Install

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

Composer 2 already registers the package autoloader through
`vendor/autoload.php`.

### CodeIgniter 4

`app/Config/Paths.php` already requires `vendor/autoload.php`, so the classes are
available in controllers, models and commands without extra wiring.

### CodeIgniter 3

Set `$config['composer_autoload'] = TRUE;` in `application/config/config.php`, or
add `require_once APPPATH . '../vendor/autoload.php';` to `index.php`.

### CodeIgniter 2

`index.php` does not know Composer. Add the autoloader yourself, right after the
`APPPATH` constant is defined:

```php
require_once dirname(__DIR__) . '/vendor/autoload.php';
```

Modular extensions (MX) are not involved: the package is plain PHP and does not
need a module.

## 2. Repository

CodeIgniter 4 with the query builder:

```php
<?php

namespace App\Libraries;

use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\Model\Pds;

final class PdsRepository implements PdsRepositoryInterface
{
    public function __construct(private readonly \CodeIgniter\Database\BaseConnection $db)
    {
    }

    public function find(string $employeeId): ?Pds
    {
        $personal = $this->db->table('pds_personal_info')
            ->where('employee_id', $employeeId)
            ->get()
            ->getRowArray();

        if ($personal === null) {
            return null;
        }

        return Pds::fromArray([
            'employee_id' => $employeeId,
            'personal' => $personal,
            'family' => $this->one('pds_family_background', $employeeId),
            'children' => $this->rows('pds_children', $employeeId),
            'education' => $this->rows('pds_education_background', $employeeId),
            'eligibility' => $this->rows('pds_eligibility', $employeeId),
            'work' => $this->rows('pds_work_experience', $employeeId),
            'volunteer' => $this->rows('pds_organization', $employeeId),
            'training' => $this->rows('pds_training', $employeeId),
            'other_info' => $this->rows('pds_other_info', $employeeId),
            'questions' => $this->rows('pds_questions', $employeeId),
            'references' => $this->rows('pds_references', $employeeId),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function one(string $table, string $employeeId): ?array
    {
        return $this->db->table($table)->where('employee_id', $employeeId)->get()->getRowArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, string $employeeId): array
    {
        return $this->db->table($table)->where('employee_id', $employeeId)->get()->getResultArray();
    }
}
```

CodeIgniter 2/3 with the legacy models instead of SQL:

```php
<?php

class Pds_repository implements \KawayanMedia\Pds\Contract\PdsRepositoryInterface
{
    public function find($employeeId)
    {
        $personal = new Personal();
        $personal->get_by_employee_id($employeeId);

        if ( ! $personal->exists())
        {
            return NULL;
        }

        $family = new Family();
        $family->get_by_employee_id($employeeId);

        $children = new Children();
        $children->get_by_employee_id($employeeId);

        $education = new Education();
        $education->where('employee_id', $employeeId)->get();

        return \KawayanMedia\Pds\Model\Pds::fromArray([
            'employee_id' => $employeeId,
            'personal' => $personal->to_array(),
            'family' => $family->exists() ? $family->to_array() : NULL,
            'children' => $children->all_to_array(),
            'education' => $education->all_to_array(),
            // … the remaining sections
        ]);
    }
}
```

## 3. Controller

### CodeIgniter 4

```php
<?php

namespace Modules\Pds\Controllers;

use App\Controllers\BaseController;
use App\Libraries\PdsRepository;
use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\PdsGenerator;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;

final class PdsPdf extends BaseController
{
    public function print(string $employeeId)
    {
        $generator = new PdsGenerator(
            new PdsRepository(db_connect()),
            new PdsPdfRenderer(
                PdsLayout::fromDirectory(ROOTPATH . 'forms/pds'),
                new PdsOptions(accomplishedOn: $this->request->getGet('as_of')),
            ),
        );

        $pds = $generator->find($employeeId);

        if ($pds === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($generator->render($pds));
    }
}
```

### CodeIgniter 2/3

`stream()` and `download()` send the FPDF headers through `header()`, which is
what the legacy controller did with `Output(..., 'I')`; call `exit` afterwards so
the framework does not append its own output:

```php
public function print_pds($employee_id)
{
    $generator = new \KawayanMedia\Pds\PdsGenerator(
        new Pds_repository(),
        new \KawayanMedia\Pds\Renderer\PdsPdfRenderer(
            \KawayanMedia\Pds\Config\PdsLayout::fromDirectory(FCPATH . 'forms/pds')
        )
    );

    $generator->renderer()->stream($generator->pds($employee_id), 'pds-' . $employee_id . '.pdf');

    exit;
}
```

To keep the file instead of streaming it:

```php
$generator->save($employee_id, FCPATH . 'pds/' . $employee_id . '.pdf');
```

## 4. Replacing the legacy screen

The old screen wrote one temporary PDF per page into
`dtr/template/pds/page{N}_{employee_id}.pdf` and then concatenated them with the
`concat_pdf` library. With the package that whole chain becomes:

```php
$generator->renderer()->stream($generator->pds($employee_id), 'pds.pdf');
```

Nothing is written to disk, so there is no cleanup step and no risk of serving a
half-written archive when two users print the same employee at once.

## 5. Templates

The package bundles the four CS Form 212 pages it was built against
(`vendor/kawayanmedia/pds/resources/templates`). To print on the agency's own
scan, copy the files anywhere readable and pass the directory:

```php
PdsLayout::fromDirectory(FCPATH . 'dtr/template/pds');
```

That directory is exactly the one the legacy controller used, so no change to the
existing assets is needed:

```php
PdsLayout::fromDirectory(FCPATH . 'dtr/template/pds');   // page1.pdf … page4.pdf
```
