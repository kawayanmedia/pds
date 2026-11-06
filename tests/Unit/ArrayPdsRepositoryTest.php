<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Tests\Unit;

use KawayanMedia\Pds\Adapter\ArrayPdsRepository;
use KawayanMedia\Pds\Tests\Support\PdsFixture;
use PHPUnit\Framework\TestCase;

final class ArrayPdsRepositoryTest extends TestCase
{
    public function test_it_builds_an_aggregate_from_legacy_rows(): void
    {
        $repository = new ArrayPdsRepository(['013001' => PdsFixture::rows()]);

        $pds = $repository->find('013001');

        self::assertNotNull($pds);
        self::assertSame('Dela Cruz', $pds->personal->lastName);
    }

    public function test_unknown_ids_return_null(): void
    {
        $repository = new ArrayPdsRepository();

        self::assertNull($repository->find('999'));
        self::assertFalse($repository->has('999'));
    }

    public function test_records_can_be_added_after_construction(): void
    {
        $repository = new ArrayPdsRepository();
        $repository->add('013001', PdsFixture::pds());

        self::assertTrue($repository->has('013001'));
        self::assertSame(['013001'], $repository->ids());
        self::assertSame('Dela Cruz', $repository->find('013001')?->personal->lastName);
    }

    public function test_the_employee_id_is_filled_in_when_missing(): void
    {
        $rows = PdsFixture::rows();
        unset($rows['employee_id']);

        $repository = new ArrayPdsRepository(['013002' => $rows]);

        self::assertSame('013002', $repository->find('013002')?->employeeId);
    }
}
