<?php

declare(strict_types=1);

namespace KawayanMedia\Pds\Config;

/**
 * Absolute placements drawn straight onto one template page.
 */
final class PageLayout
{
    /**
     * @param list<Placement> $placements
     */
    public function __construct(public readonly array $placements = [])
    {
    }

    public static function make(Placement ...$placements): self
    {
        return new self(array_values($placements));
    }
}
