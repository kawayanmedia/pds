<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Contract;

/**
 * Persistence seam of the write side.
 *
 * Sections are named with {@see \KawayanMedia\Pds\Support\PdsSection}; the
 * shipped adapters are PdoPdsStore (any PDO database through
 * kawayanmedia/persistence) and ArrayPdsStore (arrays, for tests and for
 * bridging an existing data layer).
 *
 * Implement this interface when a host application must write through its own
 * ORM instead of the bundled adapters.
 */
interface PdsStoreInterface
{
    /**
     * Every row of a section for one employee, in insertion order.
     *
     * @return list<array<string, mixed>>
     */
    public function find(string $section, string $employeeId): array;

    /**
     * The single row of a section, or null when the employee has none.
     *
     * @return array<string, mixed>|null
     */
    public function first(string $section, string $employeeId): ?array;

    /**
     * Inserts the row of a single-row section, or updates it when it exists.
     *
     * @param array<string, mixed> $values
     */
    public function save(string $section, string $employeeId, array $values): void;

    /**
     * Updates the row of a single-row section when it exists and does nothing
     * otherwise. Used for the employee record, which the PDS does not own.
     *
     * @param array<string, mixed> $values
     */
    public function sync(string $section, string $employeeId, array $values): void;

    /**
     * Replaces the employee's rows in a section: delete, then insert.
     *
     * @param list<array<string, mixed>> $rows  replacement rows, in order
     * @param array<string, mixed>       $scope extra equality filter applied to the delete and merged into every row (for example ['level' => 2])
     */
    public function replace(string $section, string $employeeId, array $rows, array $scope = []): void;

    /**
     * @template T
     *
     * @param callable(self): T $operation
     *
     * @return T
     */
    public function transaction(callable $operation): mixed;
}
