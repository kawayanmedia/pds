<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Adapter;

use KawayanMedia\Pds\Config\PdsTables;
use KawayanMedia\Pds\Contract\PdsStoreInterface;
use KawayanMedia\Persistence\Contract\DatabaseInterface;

/**
 * Stores PDS data in any database reachable through
 * {@see DatabaseInterface}.
 *
 * ```php
 * $store = new PdoPdsStore(PdoDatabase::fromDsn('mysql:host=localhost;dbname=ihrmis', 'root', 'secret'));
 * (new PdsEditor($store))->savePersonal('1234', $post);
 * ```
 */
final class PdoPdsStore implements PdsStoreInterface
{
    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly PdsTables $tables = new PdsTables(),
    ) {
    }

    public function find(string $section, string $employeeId): array
    {
        return $this->database->select(
            $this->tables->table($section),
            ['*'],
            [$this->tables->key($section) => $employeeId],
        );
    }

    public function first(string $section, string $employeeId): ?array
    {
        $rows = $this->database->select(
            $this->tables->table($section),
            ['*'],
            [$this->tables->key($section) => $employeeId],
            ['limit' => 1],
        );

        return $rows[0] ?? null;
    }

    public function save(string $section, string $employeeId, array $values): void
    {
        $key = $this->tables->key($section);

        if ($this->first($section, $employeeId) === null) {
            $this->database->insert(
                $this->tables->table($section),
                [$key => $employeeId] + $values,
            );

            return;
        }

        $this->database->update(
            $this->tables->table($section),
            $values,
            [$key => $employeeId],
        );
    }

    public function sync(string $section, string $employeeId, array $values): void
    {
        if ($values === []) {
            return;
        }

        $this->database->update(
            $this->tables->table($section),
            $values,
            [$this->tables->key($section) => $employeeId],
        );
    }

    public function replace(string $section, string $employeeId, array $rows, array $scope = []): void
    {
        $table = $this->tables->table($section);
        $key = $this->tables->key($section);
        $conditions = [$key => $employeeId] + $scope;

        $this->database->transaction(function (DatabaseInterface $database) use ($table, $key, $employeeId, $rows, $scope, $conditions): void {
            $database->delete($table, $conditions);

            foreach ($rows as $row) {
                $database->insert($table, [$key => $employeeId] + $scope + $row);
            }
        });
    }

    public function transaction(callable $operation): mixed
    {
        return $this->database->transaction(fn (): mixed => $operation($this));
    }

    public function tables(): PdsTables
    {
        return $this->tables;
    }
}
