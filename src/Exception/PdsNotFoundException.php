<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Exception;

use RuntimeException;

final class PdsNotFoundException extends RuntimeException
{
    public static function forEmployee(string $employeeId): self
    {
        return new self('No PDS record found for employee ' . $employeeId . '.');
    }
}
