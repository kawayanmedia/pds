# Using `kawayanmedia/pds` in Laravel

The package has no framework dependency. In Laravel you bind the repository in a
service provider, keep the layout in the container and stream the PDF from a
controller.

## 1. Install

```bash
composer require kawayanmedia/pds
```

While the package lives inside the iHRMIS repository:

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

## 2. Repository

```php
<?php

namespace App\Pds;

use Illuminate\Support\Facades\DB;
use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\Model\Pds;

final class DatabasePdsRepository implements PdsRepositoryInterface
{
    public function find(string $employeeId): ?Pds
    {
        $personal = DB::table('pds_personal_info')->where('employee_id', $employeeId)->first();

        if ($personal === null) {
            return null;
        }

        return Pds::fromArray([
            'employee_id' => $employeeId,
            'personal' => (array) $personal,
            'family' => (array) DB::table('pds_family_background')->where('employee_id', $employeeId)->first(),
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
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, string $employeeId): array
    {
        return DB::table($table)
            ->where('employee_id', $employeeId)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }
}
```

Eloquent works too — map each model to an array with `$model->toArray()` or pass
the models through `Pds::fromArray()` for the typed collections
(`children`, `education`, `work`, …), which accept either.

## 3. Service provider

```php
<?php

namespace App\Providers;

use App\Pds\DatabasePdsRepository;
use Illuminate\Support\ServiceProvider;
use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\PdsGenerator;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;

final class PdsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PdsRepositoryInterface::class, DatabasePdsRepository::class);

        $this->app->singleton(PdsPdfRenderer::class, static fn (): PdsPdfRenderer => new PdsPdfRenderer(
            PdsLayout::fromDirectory(config('pds.templates')),
        ));

        $this->app->singleton(PdsGenerator::class, static fn ($app): PdsGenerator => new PdsGenerator(
            $app->make(PdsRepositoryInterface::class),
            $app->make(PdsPdfRenderer::class),
        ));
    }
}
```

```php
// config/pds.php
return [
    'templates' => resource_path('forms/pds'),
];
```

## 4. Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\PdsGenerator;

final class PdsController extends Controller
{
    public function __construct(private readonly PdsGenerator $pds)
    {
    }

    public function show(string $employeeId): Response
    {
        $pds = $this->pds->find($employeeId);

        abort_if($pds === null, 404, 'No Personal Data Sheet on file.');

        return response($this->pds->render($pds), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pds-' . $employeeId . '.pdf"',
        ]);
    }

    public function download(string $employeeId)
    {
        $pds = $this->pds->pds($employeeId);

        $path = storage_path('app/pds/' . $employeeId . '.pdf');

        $this->pds->renderer()->save($pds, $path);

        return response()->download($path);
    }

    public function batch()
    {
        $ids = request('employee_ids', []);

        return response($this->pds->renderMany($ids), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
```

Routes:

```php
Route::get('/pds/{employee}/print', [PdsController::class, 'show'])->name('pds.print');
Route::get('/pds/{employee}/download', [PdsController::class, 'download'])->name('pds.download');
Route::post('/pds/batch', [PdsController::class, 'batch'])->name('pds.batch');
```

## 5. Queued generation

Generation is CPU-only and writes nothing, so it suits a job:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use KawayanMedia\Pds\PdsGenerator;

final class GeneratePds implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $employeeId,
        public readonly string $path,
        public readonly string $accomplishedOn,
    ) {
    }

    public function handle(PdsGenerator $generator): void
    {
        $generator->renderer()->withOptions(
            new \KawayanMedia\Pds\Config\PdsOptions(accomplishedOn: $this->accomplishedOn),
        )->save($generator->pds($this->employeeId), $this->path);
    }
}
```

## 6. Configuration that is not coordinates

| Need | Option |
|---|---|
| Print only the first page | `new PdsOptions(pages: [1])` |
| Back-date the accomplished date | `new PdsOptions(accomplishedOn: '2026-11-06')` |
| Change that date's format | `new PdsOptions(dateFormat: 'F j, Y')` |
| Print something other than `Present` | `new PdsOptions(presentLabel: 'To date')` |

The date is rendered with PHP's `date()` formats, so `F d, Y` prints
`November 06, 2026`.
