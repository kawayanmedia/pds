<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Contract;

use KawayanMedia\Pds\Model\Pds;

/**
 * The only contract a host application has to implement: load the rows of one
 * employee and hand them back as a Pds aggregate.
 */
interface PdsRepositoryInterface
{
    public function find(string $employeeId): ?Pds;
}
