<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Adapter;

use KawayanMedia\Pds\Contract\PdsStoreInterface;
use KawayanMedia\Pds\Support\PdsSection;

/**
 * Array backed store for tests, demos and for bridging an existing data layer
 * that already holds the PDS rows in memory.
 *
 * ```php
 * $store = new ArrayPdsStore([
 *     PdsSection::PERSONAL => ['1234' => ['lname' => 'Dela Cruz', 'fname' => 'Juan']],
 * ]);
 * ```
 */
final class ArrayPdsStore implements PdsStoreInterface
{
    /**
     * @var array<string, array<string, list<array<string, mixed>>>>
     */
    private array $rows = [];

    /**
     * @param array<string, array<string, array<string, mixed>|list<array<string, mixed>>>> $data section => employee id => row(s)
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $section => $employees) {
            foreach ($employees as $employeeId => $rows) {
                $list = $rows;

                if ($list === [] || !array_is_list($list)) {
                    $list = [$rows];
                }

                $this->rows[$section][(string) $employeeId] = array_values($list);
            }
        }
    }

    public function find(string $section, string $employeeId): array
    {
        PdsSection::assert($section);

        return $this->rows[$section][$employeeId] ?? [];
    }

    public function first(string $section, string $employeeId): ?array
    {
        return $this->find($section, $employeeId)[0] ?? null;
    }

    public function save(string $section, string $employeeId, array $values): void
    {
        $existing = $this->first($section, $employeeId);

        if ($existing === null) {
            $this->rows[$section][$employeeId] = [$values];

            return;
        }

        $this->rows[$section][$employeeId][0] = array_merge($existing, $values);
    }

    public function sync(string $section, string $employeeId, array $values): void
    {
        if ($values === [] || $this->first($section, $employeeId) === null) {
            return;
        }

        $this->save($section, $employeeId, $values);
    }

    public function replace(string $section, string $employeeId, array $rows, array $scope = []): void
    {
        PdsSection::assert($section);

        $current = $this->rows[$section][$employeeId] ?? [];

        if ($scope !== []) {
            // Keep the rows the scope does not cover; they belong to other keys
            // of the same section, such as the other education levels.
            $current = array_values(array_filter(
                $current,
                static fn (array $row): bool => !self::inScope($row, $scope),
            ));
        } else {
            $current = [];
        }

        foreach ($rows as $row) {
            $current[] = $scope + $row;
        }

        $this->rows[$section][$employeeId] = $current;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $scope
     */
    private static function inScope(array $row, array $scope): bool
    {
        foreach ($scope as $column => $value) {
            if ((string) ($row[$column] ?? '') !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    public function transaction(callable $operation): mixed
    {
        $snapshot = $this->rows;

        try {
            return $operation($this);
        } catch (\Throwable $exception) {
            $this->rows = $snapshot;

            throw $exception;
        }
    }

    /**
     * Everything stored, for assertions.
     *
     * @return array<string, array<string, list<array<string, mixed>>>>
     */
    public function dump(): array
    {
        return $this->rows;
    }

    public function truncate(string $section = ''): void
    {
        if ($section === '') {
            $this->rows = [];

            return;
        }

        unset($this->rows[$section]);
    }
}
