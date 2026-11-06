<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Adapter;

use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\Model\Pds;

/**
 * In-memory repository for tests, examples and one-off scripts.
 */
final class ArrayPdsRepository implements PdsRepositoryInterface
{
    /**
     * @param array<string, Pds|array<string, mixed>> $records employee id => aggregate or legacy rows
     */
    public function __construct(private array $records = [])
    {
    }

    public function find(string $employeeId): ?Pds
    {
        $record = $this->records[$employeeId] ?? null;

        if ($record === null) {
            return null;
        }

        if ($record instanceof Pds) {
            return $record;
        }

        $rows = $record;

        if (!array_key_exists('employee_id', $rows)) {
            $rows['employee_id'] = $employeeId;
        }

        return Pds::fromArray($rows);
    }

    /**
     * @param Pds|array<string, mixed> $record
     */
    public function add(string $employeeId, Pds|array $record): void
    {
        $this->records[$employeeId] = $record;
    }

    public function has(string $employeeId): bool
    {
        return array_key_exists($employeeId, $this->records);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->records);
    }
}
