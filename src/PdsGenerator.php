<?php

declare(strict_types=1);

namespace KawayanMedia\Pds;

use KawayanMedia\Pds\Contract\PdsRepositoryInterface;
use KawayanMedia\Pds\Exception\PdsNotFoundException;
use KawayanMedia\Pds\Model\Pds;
use KawayanMedia\Pds\Renderer\PdsPdfRenderer;

/**
 * One-call facade for the common "load then render" flow.
 */
final class PdsGenerator
{
    public function __construct(
        private readonly PdsRepositoryInterface $repository,
        private readonly PdsPdfRenderer $renderer,
    ) {
    }

    public function repository(): PdsRepositoryInterface
    {
        return $this->repository;
    }

    public function renderer(): PdsPdfRenderer
    {
        return $this->renderer;
    }

    public function find(string $employeeId): ?Pds
    {
        return $this->repository->find($employeeId);
    }

    public function pds(string $employeeId): Pds
    {
        return $this->repository->find($employeeId) ?? throw PdsNotFoundException::forEmployee($employeeId);
    }

    public function render(string|Pds $employee): string
    {
        return $this->renderer->render($this->resolve($employee));
    }

    public function save(string|Pds $employee, string $path): void
    {
        $this->renderer->save($this->resolve($employee), $path);
    }

    /**
     * @param iterable<string|Pds> $employees
     */
    public function renderMany(iterable $employees): string
    {
        return $this->renderer->renderMany($this->resolveAll($employees));
    }

    /**
     * @param iterable<string|Pds> $employees
     */
    public function saveMany(iterable $employees, string $path): void
    {
        $this->renderer->save($this->resolveAll($employees), $path);
    }

    private function resolve(string|Pds $employee): Pds
    {
        return $employee instanceof Pds ? $employee : $this->pds($employee);
    }

    /**
     * @param iterable<string|Pds> $employees
     *
     * @return list<Pds>
     */
    private function resolveAll(iterable $employees): array
    {
        $documents = [];

        foreach ($employees as $employee) {
            $documents[] = $this->resolve($employee);
        }

        return $documents;
    }
}
