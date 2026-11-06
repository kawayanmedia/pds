<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Feature;

use KawayanMedia\Pds\Adapter\ArrayPdsRepository;
use KawayanMedia\Pds\Config\PdsLayout;
use KawayanMedia\Pds\Config\PdsOptions;
use KawayanMedia\Pds\Exception\PdsNotFoundException;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Model\Training;
use KawayanMedia\Pds\PdsGenerator;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;
use KawayanMedia\Pds\Tests\Support\PdsFixture;
use KawayanMedia\Pds\Tests\Support\TemplateFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdsPdfRendererTest extends TestCase
{
    private string $directory;

    /** @var array<string, string> */
    private array $templates;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/pds-pdf-' . bin2hex(random_bytes(6));
        $this->templates = TemplateFactory::create($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*.pdf') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function test_it_renders_the_four_form_pages(): void
    {
        $pdf = $this->renderer()->render(PdsFixture::pds());

        self::assertStringStartsWith('%PDF', $pdf);
        self::assertSame(4, TemplateFactory::pageCount($pdf));
        self::assertStringContainsString('PAGE1 TEMPLATE', $pdf);
        self::assertStringContainsString('PAGE4 TEMPLATE', $pdf);
    }

    public function test_page_one_prints_the_personal_and_family_sections(): void
    {
        $pdf = $this->renderer()->render(PdsFixture::pds());

        foreach (['Filipino', 'Boac, Marinduque', 'Dela Cruz', 'Teacher', 'Ana'] as $expected) {
            self::assertStringContainsString($expected, $pdf);
        }

        self::assertStringContainsString('03-14-1985', $pdf);
        self::assertStringContainsString('Jose Dela Cruz', $pdf);
        self::assertStringContainsString('06/01/2012', $pdf);
        // Names are printed one character per pre-printed box.
        self::assertStringContainsString('(D) Tj', $pdf);
    }

    public function test_page_two_prints_eligibility_and_work_experience(): void
    {
        $pdf = $this->renderer()->render(PdsFixture::pds());

        self::assertStringContainsString('Career Service Professional', $pdf);
        self::assertStringContainsString('04/23/2006', $pdf);
        self::assertStringContainsString('Computer Operator', $pdf);
        self::assertStringContainsString('Present', $pdf);
        self::assertStringContainsString('Yes', $pdf);
    }

    public function test_page_three_prints_both_volunteer_dates_and_the_position(): void
    {
        $pdf = $this->renderer()->render(PdsFixture::pds());

        self::assertStringContainsString('Barangay Malusak Youth Council', $pdf);
        self::assertStringContainsString('01/01/2003', $pdf);
        self::assertStringContainsString('12/31/2004', $pdf);
        self::assertStringContainsString('Secretary', $pdf);
        self::assertStringContainsString('Database administration', $pdf);
        self::assertStringContainsString('Data Privacy Act Orientation', $pdf);
    }

    public function test_page_four_prints_references_ctc_and_the_accomplished_date(): void
    {
        $pdf = $this->renderer(new PdsOptions(accomplishedOn: '2026-11-06'))->render(PdsFixture::pds());

        self::assertStringContainsString('Maria Santos', $pdf);
        self::assertStringContainsString('12345678', $pdf);
        self::assertStringContainsString('01/05/2026', $pdf);
        self::assertStringContainsString('November 06, 2026', $pdf);
    }

    public function test_a_subset_of_pages_can_be_printed(): void
    {
        $renderer = $this->renderer(new PdsOptions(pages: [1]));

        $pdf = $renderer->render(PdsFixture::pds());

        self::assertSame(1, TemplateFactory::pageCount($pdf));
        self::assertStringContainsString('PAGE1 TEMPLATE', $pdf);
        self::assertStringNotContainsString('PAGE2 TEMPLATE', $pdf);
    }

    public function test_multiple_employees_become_multiple_four_page_forms(): void
    {
        $renderer = $this->renderer();

        $pdf = $renderer->renderMany([
            PdsFixture::pds('013001'),
            PdsFixture::pds('013002'),
        ]);

        self::assertSame(8, TemplateFactory::pageCount($pdf));
    }

    public function test_a_long_training_list_continues_on_another_template_page(): void
    {
        $rows = PdsFixture::rows();
        $rows['training'] = [];

        for ($index = 1; $index <= 25; $index++) {
            $rows['training'][] = [
                'name' => 'Training ' . $index,
                'date_from' => '2025-01-01',
                'date_to' => '2025-01-02',
                'number_hours' => '8',
                'conducted_by' => 'CSC',
            ];
        }

        $pdf = $this->renderer()->render(Pds::fromArray($rows));

        self::assertSame(5, TemplateFactory::pageCount($pdf));
        self::assertStringContainsString('Training 25', $pdf);
    }

    public function test_the_report_is_written_to_a_file(): void
    {
        $path = $this->directory . '/out.pdf';

        $this->renderer()->save(PdsFixture::pds(), $path);

        self::assertFileExists($path);
        self::assertStringStartsWith('%PDF', (string) file_get_contents($path));
    }

    public function test_a_missing_template_is_reported(): void
    {
        $layout = PdsLayout::legacyIhrmis([
            PdsLayout::PAGE_PERSONAL => $this->directory . '/missing.pdf',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDS template not found');

        (new PdsPdfRenderer($layout))->render(PdsFixture::pds());
    }

    public function test_the_generator_loads_and_renders_by_employee_id(): void
    {
        $generator = new PdsGenerator(
            new ArrayPdsRepository(['013001' => PdsFixture::rows()]),
            $this->renderer(),
        );

        self::assertNotNull($generator->find('013001'));
        self::assertSame(4, TemplateFactory::pageCount($generator->render('013001')));
        self::assertSame(8, TemplateFactory::pageCount($generator->renderMany(['013001', '013001'])));
        self::assertNull($generator->find('404'));
    }

    public function test_the_generator_reports_an_unknown_employee(): void
    {
        $generator = new PdsGenerator(new ArrayPdsRepository(), $this->renderer());

        $this->expectException(PdsNotFoundException::class);

        $generator->render('404');
    }

    public function test_the_engine_renders_directly_from_a_model(): void
    {
        $pds = PdsFixture::pds();
        $withoutTrainings = new Pds(
            employeeId: $pds->employeeId,
            personal: $pds->personal,
            training: [new Training(name: 'Only Training')],
        );

        $pdf = $this->renderer()->render($withoutTrainings);

        self::assertStringContainsString('Only Training', $pdf);
        self::assertStringNotContainsString('Data Privacy Act Orientation', $pdf);
    }

    private function renderer(?PdsOptions $options = null): PdsPdfRenderer
    {
        $layout = PdsLayout::legacyIhrmis($this->templates, compress: false);

        return new PdsPdfRenderer($layout, $options ?? new PdsOptions());
    }
}
