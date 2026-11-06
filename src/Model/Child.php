<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Model;

use KawayanMedia\Pds\Support\Dates;
use KawayanMedia\Pds\Support\Text;

/**
 * Row of the legacy pds_children table.
 */
final class Child
{
    public function __construct(
        public readonly string $name = '',
        public readonly mixed $birthDate = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            name: Text::blank($row['children'] ?? $row['name'] ?? ''),
            birthDate: $row['birth_date'] ?? null,
        );
    }

    public function birthDateLabel(): string
    {
        return Dates::slashDate($this->birthDate);
    }
}
