<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;

/**
 * `LIMIT` keyword parser.
 */
final class Limit implements Component
{
    /**
     * The number of rows skipped.
     */
    public int|string $offset;

    /**
     * The number of rows to be returned.
     */
    public int|string $rowCount;

    /**
     * @param int|string $rowCount the row count
     * @param int|string $offset   the offset
     */
    public function __construct(int|string $rowCount = 0, int|string $offset = 0)
    {
        $this->rowCount = $rowCount;
        $this->offset = $offset;
    }

    public function build(): string
    {
        return $this->offset . ', ' . $this->rowCount;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
